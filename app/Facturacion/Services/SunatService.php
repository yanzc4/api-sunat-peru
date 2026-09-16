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
use Greenter\Ws\Services\SunatEndpoints;
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
            $mensaje = "Error SOAP: " . ($e->getMessage() ?: 'Conexión fallida');
            $this->comprobanteRepo->updateEstado(
                $comprobante->id,
                'error',
                null,
                $mensaje
            );
            throw new SunatException($mensaje);

        } catch (\Exception $e) {
            $mensaje = "Error inesperado: " . $e->getMessage();
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
            throw new SunatException(
                "Error SOAP consultando estado: " . $e->getMessage()
            );
        } catch (\Exception $e) {
            throw new SunatException(
                "Error consultando estado: " . $e->getMessage()
            );
        }
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
            throw new SunatException(
                "Error SOAP consultando CDR: " . $e->getMessage()
            );
        } catch (\Exception $e) {
            throw new SunatException(
                "Error consultando CDR: " . $e->getMessage()
            );
        }
    }

    private function createBillSender(EmpresaFacturacion $empresa): BillSender
    {
        $solPassword = $this->encryption->decrypt($empresa->solPassword);
        $endpoint = $this->getEndpoint($empresa->entorno);

        $soapClient = new SoapClient();
        $soapClient->setCredentials($empresa->solUsuario, $solPassword);
        $soapClient->setService($endpoint);

        $builder = new ServiceBuilder();
        $builder->setClient($soapClient);

        return $builder->build(BillSender::class);
    }

    private function createConsultService(EmpresaFacturacion $empresa): ConsultCdrService
    {
        $solPassword = $this->encryption->decrypt($empresa->solPassword);
        $endpoint = SunatEndpoints::FE_CONSULTA_CDR;

        $soapClient = new SoapClient();
        $soapClient->setCredentials($empresa->solUsuario, $solPassword);
        $soapClient->setService($endpoint);

        $builder = new ServiceBuilder();
        $builder->setClient($soapClient);

        return $builder->build(ConsultCdrService::class);
    }

    private function getEndpoint(string $entorno): string
    {
        return $entorno === 'produccion'
            ? SunatEndpoints::FE_PRODUCCION
            : SunatEndpoints::FE_BETA;
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

        $error = $result->getError();
        if ($error !== null) {
            $data['error_code'] = $error->getCode();
            $data['error_message'] = $error->getMessage();
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
