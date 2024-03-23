<?php

dol_include_once('/cfdixml/vendor/autoload.php');


// use \CfdiUtils\Elements\Pagos20;
use \CfdiUtils\Elements\Pagos20\Pagos;
use \CfdiUtils\CfdiCreator40;
use \CfdiUtils\TimbreFiscalDigital\TfdCadenaDeOrigen;
use \PhpCfdi\Credentials\PrivateKey;
use \PhpCfdi\Finkok\FinkokEnvironment;
use \PhpCfdi\Finkok\FinkokSettings;
use \PhpCfdi\Finkok\QuickFinkok;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\XmlCancelacion\Models\CancelDocument;
use PhpCfdi\CfdiExpresiones\DiscoverExtractor;
use Phpcfdi\Finkok\Services\Utilities;

class CfdiUtils extends CommonInvoice
{
    //TODO: Descuentos, no los toma.


    public function preCfdi($comprobanteAtributos, $emisor, $receptor, $conceptos, $adicionales = null, $cer, $key, $passkey)
    {


        $certificado = new \CfdiUtils\Certificado\Certificado($cer);
        $cfdirelacionados['CfdiRelacionados'] = $comprobanteAtributos['CfdiRelacionados'];
        unset($comprobanteAtributos['CfdiRelacionados']);

        $creator = new CfdiCreator40($comprobanteAtributos, $certificado);

        $comprobante = $creator->comprobante();

        // No agrego (aunque puedo) el Rfc y Nombre porque uso los que están establecidos en el certificado
        $comprobante->addEmisor($emisor);

        $comprobante->addReceptor($receptor);
        if ($receptor['Rfc'] == 'XAXX010101000') {
            $fecha = dol_print_date(dol_now(), '%Y-%m-%d');
            $fecha = explode('-', $fecha);
            $comprobante->addInformacionGlobal([
                'Periodicidad' => '01',
                'Meses' => $fecha[1],
                'Año' => $fecha[0]

            ]);
        }

        if (is_array($cfdirelacionados['CfdiRelacionados'])) {
            foreach ($cfdirelacionados as $relacion) {
                $comprobante->addCfdiRelacionados(['TipoRelacion' => $relacion['TipoRelacion']])->multiCfdiRelacionado(
                    $relacion['CfdiRelacionado']
                );
            }
        }

        if (!$adicionales) {
            $num = count($conceptos);

            for ($i = 0; $i < $num; $i++) {

                $concepto = $comprobante->addConcepto([
                    'Descripcion'       => mb_convert_encoding($conceptos[$i]['Descripcion'], 'utf8'),
                    'Cantidad'          => $conceptos[$i]['Cantidad'],
                    'ValorUnitario'     => $conceptos[$i]['ValorUnitario'],
                    'Importe'           => $conceptos[$i]['Importe'],
                    'ClaveUnidad'       => $conceptos[$i]['ClaveUnidad'],
                    'NoIdentificacion'  => $conceptos[$i]['NoIdentificacion'],
                    'ClaveProdServ'     => $conceptos[$i]['ClaveProdServ'],
                    'Descuento'         => $conceptos[$i]['Descuento'],
                    'CuentaPredial'     => $conceptos[$i]['CuentaPredial'],
                    'ObjetoImp'         => $conceptos[$i]['ObjetoImp']
                ]);
                if (array_key_exists('Impuestos', $conceptos[$i])) {
                    if (array_key_exists('Traslados', $conceptos[$i]['Impuestos'])) {
                        $concepto->addTraslado([
                            'Base'          => $conceptos[$i]['Impuestos']['Traslados']['Base'],
                            'Impuesto'      => $conceptos[$i]['Impuestos']['Traslados']['Impuesto'],
                            'TipoFactor'    => $conceptos[$i]['Impuestos']['Traslados']['TipoFactor'],
                            'TasaOCuota'    => $conceptos[$i]['Impuestos']['Traslados']['TasaOCuota'],
                            'Importe'       => $conceptos[$i]['Impuestos']['Traslados']['Importe'],
                        ]);
                    }


                    if (array_key_exists('Retencion', $conceptos[$i]['Impuestos'])) {
                        $concepto->addRetencion([
                            'Base'          => $conceptos[$i]['Impuestos']['Retencion']['Base'],
                            'Impuesto'      => $conceptos[$i]['Impuestos']['Retencion']['Impuesto'],
                            'TipoFactor'    => $conceptos[$i]['Impuestos']['Retencion']['TipoFactor'],
                            'TasaOCuota'    => $conceptos[$i]['Impuestos']['Retencion']['TasaOCuota'],
                            'Importe'       => $conceptos[$i]['Impuestos']['Retencion']['Importe'],
                        ]);
                    }
                }

                // método de ayuda para establecer las sumas del comprobante e impuestos
                // con base en la suma de los conceptos y la agrupación de sus impuestos
                $creator->addSumasConceptos(null, 2);
            }
        } else {

            $comprobante->addConcepto([

                'Descripcion'   => "Pago",
                'Cantidad'      => 1,
                'ValorUnitario' => 0,
                'Importe'       => 0,
                'ClaveUnidad'        => "ACT",
                'ClaveProdServ' => "84111506",
                'ObjetoImp'     => "01",

            ]);

            $PagosComplemento = new Pagos();

            foreach ($adicionales as $keys => $complementos) {

                $pago = $PagosComplemento->addPago([
                    //* Atributos */
                    'FechaPago'     => $complementos['pago']['FechaPago'],
                    'FormaDePagoP'  => $complementos['pago']['FormaDePagoP'],
                    'Monto'         => $complementos['pago']['Monto'],
                    'MonedaP'       => $complementos['pago']['MonedaP'],
                    'TipoCambioP'   => $complementos['pago']['TipoCambioP']

                ]);

                $num = count($complementos['pago']['DoctoRelacionado']);
                $i = 0;
                $ImpuestosDR = [];
                while ($i < $num) {

                    $ImpuestosDR = $complementos['pago']['DoctoRelacionado'][$i]['ImpuestosDR'];
                    // echo '<pre>';print_r($complementos['pago']);exit;
                    $docrelacionado = $pago->addDoctoRelacionado([
                        //* Atributos */
                        'IdDocumento' => $complementos['pago']['DoctoRelacionado'][$i]['IdDocumento'],
                        'MonedaDR' => $complementos['pago']['DoctoRelacionado'][$i]['MonedaDR'],
                        'NumParcialidad' => $complementos['pago']['DoctoRelacionado'][$i]['NumParcialidad'],
                        'ImpSaldoAnt' => $complementos['pago']['DoctoRelacionado'][$i]['ImpSaldoAnt'],
                        'ImpPagado' => $complementos['pago']['DoctoRelacionado'][$i]['ImpPagado'],
                        'ImpSaldoInsoluto' => $complementos['pago']['DoctoRelacionado'][$i]['ImpSaldoInsoluto'],
                        'EquivalenciaDR' => $complementos['pago']['DoctoRelacionado'][$i]['EquivalenciaDR'],
                        'ObjetoImpDR' => $complementos['pago']['DoctoRelacionado'][$i]['ObjetoImpDR']
                    ]);
                    $impuestosDoctoR = $docrelacionado->addImpuestosDR();


                    foreach ($ImpuestosDR as $impuestoDR) {

                        $impuestosDoctoR->getTrasladosDR()->addTrasladoDR([
                            'BaseDR' => $impuestoDR['BaseDR'],
                            'ImporteDR' => $impuestoDR['ImporteDR'],
                            'ImpuestoDR' => $impuestoDR['ImpuestoDR'],
                            'TasaOCuotaDR' => $impuestoDR['TasaOCuotaDR'],
                            'TipoFactorDR' => $impuestoDR['TipoFactorDR'],
                        ]);
                    }
                    $i++;
                }


                $trasladosPago = $pago->addImpuestosP();

                $i = 0;
                // echo '<pre>';print_r($complementos['pago']['Impuestosp']);exit;
                $num = count($complementos['pago']['Impuestosp']['Traslados']);


                while ($i < $num) {

                    $trasladosPago->getTrasladosP()->addTrasladoP($complementos['pago']['Impuestosp']['Traslados'][$i]);
                    // echo '<pre>';print_r($complementos['pago']['Impuestosp']['Traslados'][$i]);exit;
                    $PagosComplemento->addTotales($complementos['totales'][$i]);
                    $i++;
                }


                //    echo '<pre>';print_r($complementos['pago']['totales']['Traslados']);exit;

            }
            $comprobante->addComplemento($PagosComplemento);
            $creator->addSumasConceptos(null, 0);
        }

        $pemPrivateKeyContents = PrivateKey::convertDerToPem(file_get_contents('file://' . $key), $passkey !== '');

        // método de ayuda para generar el sello (obtener la cadena de origen y firmar con la llave privada)
        $creator->addSello($pemPrivateKeyContents, $passkey);

        // método de ayuda para mover las declaraciones de espacios de nombre al nodo raíz
        $creator->moveSatDefinitionsToComprobante();

        // método de ayuda para validar usando las validaciones estándar de creación de la librería
        $asserts = $creator->validate();
        $asserts->hasErrors(); // contiene si hay o no errores

        return $creator->asXml();
    }

    public function validate($xml)
    {

        $cfdi = \CfdiUtils\Cfdi::newFromString($xml);
        $cfdi->getVersion(); // (string) 3.3
        $cfdi->getDocument(); // clon del objeto DOMDocument
        $cfdi->getSource(); // (string) <cfdi:Comprobante...
        return $cfdi->getNode(); // Nodo de trabajo del nodo cfdi:Comprobante

    }

    public function quickStamp($precfdi, $token, $mode, User $user = null)
    {
        global $conf;

        if ($mode == 'TEST') $settings = new FinkokSettings(strtolower($conf->global->MAIN_INFO_SIREN), $token, FinkokEnvironment::makeDevelopment());
        if ($mode == 'PRODUCTION') $settings = new FinkokSettings(strtolower($conf->global->MAIN_INFO_SIREN), $token, FinkokEnvironment::makeProduction());
        $finkok = new QuickFinkok($settings);

        $stampResult = $finkok->stamp($precfdi); // <- aquí contactamos a Finkok


        if ($stampResult->hasAlerts()) { // stamp es un objeto con propiedades nombradas
            foreach ($stampResult->alerts() as $alert) {
                $data = [
                    'code' => $alert->errorCode(),
                    'message' => $alert->message(),
                ];
                if ($alert->errorCode() == '307') {
                    $data['data'] = $stampResult->xml();
                }
            }
        } else {
            //Add trigger biut
            // if ($user) {
            //     $result = $this->call_trigger('CFDI_STAMP', $user);
            // }
            $data = [
                'code' => '200',
                'data' => $stampResult->xml()
            ];
        }
        return $data; // CFDI firmado
    }

    public function CancelDocument($uuid, $reason, $mode, $cer, $key, $passkey, $token)
    {
        global $conf;
        $file = file_get_contents('file://' . $key);

        // $pemPrivateKeyContents = PrivateKey::convertDerToPem(file_get_contents('file://' . $key), $passkey !== '');
        $credential = Credential::openFiles($cer, 'file://' . $key, $passkey);
        if ($mode == 'TEST') $settings = new FinkokSettings(strtolower($conf->global->MAIN_INFO_SIREN), $token, FinkokEnvironment::makeDevelopment());
        if ($mode == 'PRODUCTION') $settings = new FinkokSettings(strtolower($conf->global->MAIN_INFO_SIREN), $token, FinkokEnvironment::makeProduction());

        $finkok = new QuickFinkok($settings);

        if ($reason == '01') {
            $documentToCancel = CancelDocument::newWithErrorsRelated(
                $uuid['cancelacion'],  // el UUID a cancelar
                $uuid['sustitucion']   // el UUID que lo sustituye
            );
        }

        if ($reason == '02') {
            $documentToCancel = CancelDocument::newWithErrorsUnrelated(
                $uuid
            );
        }
        if ($reason == '03') {
            $documentToCancel = CancelDocument::newNotExecuted(
                $uuid
            );
        }
        if ($reason == '04') {
            $documentToCancel = CancelDocument::newNormativeToGlobal(
                $uuid
            );
        }
        // Presentar la solicitud de cancelación
        $result = $finkok->cancel($credential, $documentToCancel);
        return $result;
    }

    public function getData($xml)
    {
        // clean cfdi

        $cfdi = \CfdiUtils\Cfdi::newFromString($xml);
        $cfdi->getVersion(); // (string) 3.3
        $cfdi->getDocument(); // clon del objeto DOMDocument
        $cfdi->getSource(); // (string) <cfdi:Comprobante...
        $comprobante = $cfdi->getNode(); // Nodo de trabajo del nodo cfdi:Comprobante
        $tfd = $comprobante->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');
        $emisor = $comprobante->searchNode('cfdi:Emisor');
        $receptor = $comprobante->searchNode('cfdi:Receptor');
        $tfdXmlString = \CfdiUtils\Nodes\XmlNodeUtils::nodeToXmlString($tfd);
        $builder = new TfdCadenaDeOrigen();
        $tfdCaenaOrigen = $builder->build($tfdXmlString);

        $data = [
            'SelloCFD'    => $tfd['SelloCFD'],
            'SelloSAT'    => $tfd['SelloSAT'],
            'CertCFD'    => $comprobante['NoCertificado'],
            'FechaTimbrado' => $tfd['FechaTimbrado'],
            'FechaEmision' => $comprobante['Fecha'],
            'UUID' => $tfd['UUID'],
            'CertSAT' => $tfd['NoCertificadoSAT'],
            'CadenaOriginal' => $tfdCaenaOrigen,
            'EmisorRfc' =>  $emisor['Rfc'],
            'ReceptorRfc' =>  $receptor['Rfc'],
            'Total' => $comprobante['Total'],
        ];

        return $data;
    }

    public function getExpression($xml)
    {


        echo '<pre>';
        print_r($xml);
        exit;
        // creamos el extractor>p
        $extractor = new DiscoverExtractor();

        // abrimos el documento en un DOMDocument
        $document = new DOMDocument();
        $document->load($xml);

        // // obtenemos la expresión
        $expression = $extractor->extract($document);

        // // y también podemos obtener los valores individuales
        // $values = $extractor->obtain($document);

        return;
    }

    // public function reportCredit($mode,$token,$rfc)
    // {

    //     global $conf;

    //     if ($mode == 'TEST') $settings = new FinkokSettings('informaticalafell@gmail.com', 'dnc498021', FinkokEnvironment::makeDevelopment());
    //     if ($mode == 'PRODUCTION') $settings = new FinkokSettings(strtolower($conf->global->MAIN_INFO_SIREN), $token, FinkokEnvironment::makeProduction());
    //     $finkok = new QuickFinkok($settings);


    //     $result = $finkok->reportCredits($rfc);

    //     return $result;
    // }
}
