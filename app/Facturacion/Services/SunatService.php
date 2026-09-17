<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Config\FacturacionConfig;
use App\Facturacion\Exceptions\SunatException;
use App\Facturacion\Models\Comprobante;
use App\Facturacion\Models\EmpresaFacturacion;
use App\Facturacion\Repositories\ComprobanteRepository;
use Greenter\Model\Response\BillResult;
use Greenter\Model\Response\StatusCdrResult;
use Greenter\Ws\Builder\ServiceBuilder;
use Greenter\Ws\Services\BillSender;
use Greenter\Ws\Services\ConsultCdrService;
use Greenter\Ws\Services\SoapClient;
use PDO;

class SunatService
{
    private FacturacionConfig $config;
    private EncryptionService $encryption;
    private ComprobanteRepository $comprobanteRepo;
    private \App\Facturacion\Repositories\EmpresaFacturacionRepository $empresaRepo;

    public function __construct(PDO $pdo)
    {
        $this->config = FacturacionConfig::getInstance();
        $this->encryption = new EncryptionService();
        $this->comprobanteRepo = new ComprobanteRepository($pdo);
        $this->empresaRepo = new \App\Facturacion\Repositories\EmpresaFacturacionRepository($pdo);
    }

    public function enviar(
        Comprobante $comprobante,
        EmpresaFacturacion $empresa,
        string $xmlFirmado,
        string $hash
    ): array {
        $this->comprobanteRepo->updateEstado(
            $comprobante->id,
            'enviado'
        );

        try {
            $sender = $this->createBillSender($empresa);
            $filename = sprintf(
                '%s-%s-%s',
                $empresa->ruc,
                $comprobante->tipoComprobante,
                $comprobante->getNumeroFormato()
            );

            $result = $sender->send($filename, $xmlFirmado);

            if ($result === null) {
                throw new SunatException(
                    "SUNAT no retornó respuesta"
                );
            }

            return $this->processResult($comprobante, $result, $hash);

        } catch (SunatException $e) {
            $this->comprobanteRepo->updateEstado(
                $comprobante->id,
                'error',
                null,
                $e->getMessage()
            );
            throw $e;

        } catch (\SoapFault $e) {
            error_log('Error SOAP enviando a SUNAT: ' . $e->getMessage());
            $mensaje = 'No se pudo establecer comunicación con SUNAT';
            $this->comprobanteRepo->updateEstado(
                $comprobante->id,
                'error',
                null,
                $mensaje
            );
            throw new SunatException($mensaje);

        } catch (\Exception $e) {
            error_log('Error inesperado enviando a SUNAT: ' . $e->getMessage());
            $mensaje = 'No se pudo completar el envío a SUNAT';
            $this->comprobanteRepo->updateEstado(
                $comprobante->id,
                'error',
                null,
                $mensaje
            );
            throw new SunatException($mensaje);
        }
    }

    public function consultarEstado(
        EmpresaFacturacion $empresa,
        string $ruc,
        string $tipo,
        string $serie,
        int $numero
    ): array {
        try {
            $service = $this->createConsultService($empresa);
            $result = $service->getStatus($ruc, $tipo, $serie, $numero);

            if ($result === null) {
                throw new SunatException(
                    "No se pudo obtener el estado del comprobante"
                );
            }

            return $this->processStatusResult($result);

        } catch (SunatException $e) {
            throw $e;
        } catch (\SoapFault $e) {
            error_log('Error SOAP consultando estado SUNAT: ' . $e->getMessage());
            throw new SunatException('No se pudo consultar el estado en SUNAT');
        } catch (\Exception $e) {
            error_log('Error inesperado consultando estado SUNAT: ' . $e->getMessage());
            throw new SunatException('No se pudo consultar el estado en SUNAT');
        }
    }

    /**
     * En beta SUNAT permite enviar comprobantes, pero no publica un
     * billConsultService beta equivalente. En ese entorno el estado fiable es
     * el que quedó registrado al procesar la respuesta y su CDR.
     */
    public function consultarEstadoComprobante(
        EmpresaFacturacion $empresa,
        Comprobante $comprobante
    ): array {
        if (SunatEnvironment::isBeta($empresa->entorno)) {
            return [
                'success' => $comprobante->estado === 'aceptado',
                'source' => 'local_cdr',
                'entorno' => 'beta',
                'estado' => $comprobante->estado,
                'code' => $comprobante->codigoRespuesta,
                'message' => $comprobante->mensajeRespuesta,
                'cdr_disponible' => !empty($comprobante->cdrPath),
            ];
        }

        $result = $this->consultarEstado(
            $empresa,
            $empresa->ruc,
            $comprobante->tipoComprobante,
            $comprobante->serie,
            $comprobante->correlativo
        );

        return [
            'source' => 'sunat_remote',
            'entorno' => SunatEnvironment::PRODUCCION,
            ...$result,
        ];
    }

    public function consultarCdr(
        EmpresaFacturacion $empresa,
        string $ruc,
        string $tipo,
        string $serie,
        int $numero
    ): array {
        try {
            $service = $this->createConsultService($empresa);
            $result = $service->getStatusCdr($ruc, $tipo, $serie, $numero);

            if ($result === null) {
                throw new SunatException(
                    "No se pudo obtener el CDR"
                );
            }

            $cdrData = [];

            if ($result->getCdrZip() !== null) {
                $cdrData['cdr_zip'] = $result->getCdrZip();
            }

            if ($result->getCdrResponse() !== null) {
                $cdrResponse = $result->getCdrResponse();
                $cdrData['code'] = $cdrResponse->getCode();
                $cdrData['description'] = $cdrResponse->getDescription();
                $cdrData['notes'] = $cdrResponse->getNotes();
                $cdrData['accepted'] = $cdrResponse->isAccepted();
            }

            return $cdrData;

        } catch (SunatException $e) {
            throw $e;
        } catch (\SoapFault $e) {
            error_log('Error SOAP consultando CDR: ' . $e->getMessage());
            throw new SunatException('No se pudo consultar el CDR en SUNAT');
        } catch (\Exception $e) {
            error_log('Error inesperado consultando CDR: ' . $e->getMessage());
            throw new SunatException('No se pudo consultar el CDR en SUNAT');
        }
    }

    private function createBillSender(EmpresaFacturacion $empresa): BillSender
    {
        $solPassword = $this->encryption->decrypt($empresa->solPassword);
        $endpoint = SunatEnvironment::sendEndpoint($empresa->entorno);
        $soapUsername = SunatCredentials::soapUsername($empresa->ruc, $empresa->solUsuario);

        $soapClient = new SoapClient();
        $soapClient->setCredentials($soapUsername, $solPassword);
        $soapClient->setService($endpoint);

        $builder = new ServiceBuilder();
        $builder->setClient($soapClient);

        return $builder->build(BillSender::class);
    }

    private function createConsultService(EmpresaFacturacion $empresa): ConsultCdrService
    {
        $solPassword = $this->encryption->decrypt($empresa->solPassword);
        $endpoint = SunatEnvironment::consultEndpoint($empresa->entorno);
        $soapUsername = SunatCredentials::soapUsername($empresa->ruc, $empresa->solUsuario);

        $soapClient = new SoapClient();
        $soapClient->setCredentials($soapUsername, $solPassword);
        $soapClient->setService($endpoint);

        $builder = new ServiceBuilder();
        $builder->setClient($soapClient);

        return $builder->build(ConsultCdrService::class);
    }

    private function processResult(
        Comprobante $comprobante,
        BillResult $result,
        string $hash
    ): array {
        if ($result->isSuccess()) {
            $cdrResponse = $result->getCdrResponse();
            $code = $cdrResponse ? $cdrResponse->getCode() : '0';
            $description = $cdrResponse
                ? $cdrResponse->getDescription()
                : 'Aceptado';

            $this->comprobanteRepo->updateEstado(
                $comprobante->id,
                'aceptado',
                $code,
                $description
            );

            $cdrPath = null;
            if ($result->getCdrZip() !== null) {
                $cdrPath = $this->guardarCdr(
                    $comprobante,
                    $result->getCdrZip()
                );
            }

            $this->comprobanteRepo->updateArchivos(
                $comprobante->id,
                null,
                null,
                $cdrPath,
                $hash
            );

            return [
                'success' => true,
                'estado' => 'aceptado',
                'code' => $code,
                'message' => $description,
                'cdr_path' => $cdrPath,
            ];
        }

        $error = $result->getError();
        $code = $error ? $error->getCode() : null;
        $message = $error ? $error->getMessage() : 'Error desconocido';

        $this->comprobanteRepo->updateEstado(
            $comprobante->id,
            'rechazado',
            $code,
            $message
        );

        return [
            'success' => false,
            'estado' => 'rechazado',
            'code' => $code,
            'message' => $message,
        ];
    }

    private function processStatusResult(StatusCdrResult $result): array
    {
        $error = $result->getError();
        if ($error !== null) {
            throw new SunatException(
                'SUNAT rechazó la consulta de estado: ' . $error->getMessage(),
                (string) $error->getCode(),
                $error->getMessage()
            );
        }

        $data = [
            'success' => $result->isSuccess(),
            'code' => $result->getCode(),
            'message' => $result->getMessage(),
        ];

        if ($result->getCdrResponse() !== null) {
            $cdrResponse = $result->getCdrResponse();
            $data['cdr_code'] = $cdrResponse->getCode();
            $data['cdr_description'] = $cdrResponse->getDescription();
            $data['cdr_notes'] = $cdrResponse->getNotes();
            $data['cdr_accepted'] = $cdrResponse->isAccepted();
        }

        return $data;
    }

    private function guardarCdr(
        Comprobante $comprobante,
        string $cdrZip
    ): string {
        $empresa = $this->getEmpresaForComprobante($comprobante);

        $ano = date('Y', strtotime($comprobante->fechaEmision));
        $mes = date('m', strtotime($comprobante->fechaEmision));

        $fileName = 'R-' . $empresa->ruc . '-' .
                    $comprobante->tipoComprobante . '-' .
                    $comprobante->serie . '-' .
                    str_pad((string) $comprobante->correlativo, 8, '0', STR_PAD_LEFT);

        $cdrPath = $this->config->getCdrPath(
            $empresa->ruc,
            $ano,
            $mes,
            $fileName
        );

        $directorio = dirname($cdrPath);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0750, true);
        }

        file_put_contents($cdrPath, $cdrZip);

        return $cdrPath;
    }

    private function getEmpresaForComprobante(Comprobante $comprobante): EmpresaFacturacion
    {
        $empresa = $this->empresaRepo->findById($comprobante->empresaId);

        if (!$empresa) {
            throw new SunatException("Empresa no encontrada para el comprobante");
        }

        return $empresa;
    }
}
