<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 21:47
 */

namespace App\Service;

use Greenter\Model\DocumentInterface;
use Greenter\Model\Response\BaseResult;
use Greenter\Model\Response\BillResult;
use Greenter\Model\Response\SummaryResult;
use Greenter\Report\ReportInterface;
use Greenter\Report\XmlUtils;
use Greenter\See;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class DocumentRequest
 */
class DocumentRequest implements DocumentRequestInterface
{
    /**
     * @var string
     */
    private $className;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var RequestParserInterface
     */
    private $parser;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        RequestStack $requestStack,
        RequestParserInterface $parser,
        ContainerInterface $container)
    {
        $this->requestStack = $requestStack;
        $this->parser = $parser;
        $this->container = $container;
    }

    /**
     * Set document to process.
     *
     * @param string $class
     */
    public function setDocumentType(string $class)
    {
        $this->className = $class;
    }

    /**
     * Get Result.
     *
     * @return Response
     */
    public function send($value): Response
    {
        $document = $this->getDocument();
        $see = $this->getSee($document->getCompany()->getRuc());
        $result = $see->send($document);

        if (!$result->isSuccess()) {
            // Mostrar error al conectarse a SUNAT.
            $objeto = [
                "Codigo Error" => $result->getError()->getCode(),
                "Mensaje Error" => $result->getError()->getMessage()
            ];

            return $this->json($objeto, 400);
        }
        //Guardamos el CDR
        //$tipo_doc = $document->getTipoDoc();
        if($value==true){
            $dir_to_save = "./data_sunat/";
            if (!is_dir($dir_to_save)) {
                mkdir($dir_to_save);
            }
            file_put_contents($dir_to_save.'R-'.$document->getName().'.zip', $result->getCdrZip());
        }

        $this->toBase64Zip($result);
        $xml = $see->getFactory()->getLastXml();
        $dir_to_save = "./data_sunat/";
        if (!is_dir($dir_to_save)) {
            mkdir($dir_to_save);
        }
        file_put_contents($dir_to_save.$document->getName().'.xml', $xml);

        $data = [
            'xml' => $xml,
            'hash' => $this->GetHashFromXml($xml),
            'sunatResponse' => $result
        ];
        return $this->json($data);
    }

    /**
     * Get Xml.
     *
     * @return Response
     */
    public function xml(): Response
    {
        $document = $this->getDocument();
        //$file = $document->getName();
        /**@var $errors array */
//        $errors = $this->validator->validate($document);
//        if (count($errors)) {
//            return $this->json($errors, 400);
//        }
        //error_log(print_r($document, true));
        $see = $this->getSee($document->getCompany()->getRuc());

        $xml  = $see->getXmlSigned($document);
        //error_log(print_r($see->getXmlSigned($document), true));
        $dir_to_save = "./data_sunat/";
        if (!is_dir($dir_to_save)) {
            mkdir($dir_to_save);
        }
        file_put_contents($dir_to_save.$document->getName().'.xml', $xml);

        //return $this->file($xml, $file.'.xml', 'text/xml');
        return $this->file($xml, $document->getName().'.xml', 'text/xml');
    }

    /**
     * Get Pdf.
     *
     * @return Response
     */
    public function pdf(): Response
    {
        $document = $this->getDocument();

        /**@var $errors array */
//        $errors = $this->validator->validate($document);
//        if (count($errors)) {
//            return $this->json($errors, 400);
//        }

        $jsonCompanies = $this->getParameter('companies');

        $ruc = $document->getCompany()->getRuc();
        if (empty($companies) && ($companies = json_decode($jsonCompanies, true)) && array_key_exists($ruc, $companies)) {
            $logo = $this->getFile($companies[$ruc]['logo']);
        } else {
            $logo = $this->getParameter('logo');
        }

        $parameters = [
            'system' => [
                'logo' => $logo,
//                'hash' => '',
            ],
            'user' => [
                'header' => '',
            ]
        ];

        $report = $this->getReport();
        $pdf = $report->render($document, $parameters);

        return $this->file($pdf, $document->getName().'.pdf', 'application/pdf');
    }

    /**
     * Get Configured See.
     *
     * @param string $ruc
     * @return See
     */
    public function getSee(string $ruc): See
    {
        $factory = $this->container->get(SeeFactory::class);

        return $factory->build($this->className, $ruc);
    }

    /**
     * @return DocumentInterface
     */
    private function getDocument(): DocumentInterface
    {
        $request = $this->requestStack->getCurrentRequest();

        return $this->parser->getObject($request, $this->className);
    }

    private function getReport(): ReportInterface
    {
        return $this->container->get(ReportInterface::class);
    }

    private function json($data, int $status = 200, array $headers = [])
    {
        $json = $this->container->get('serializer')->serialize($data, 'json');

        return new JsonResponse($json, $status, $headers, true);
    }

    private function file($content, string $fileName, string $contentType): Response
    {
        $response = new Response($content);

        // Create the disposition of the file
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        // Set the content disposition
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $contentType);

        return $response;
    }

    private function getParameter($key): string
    {
        $config = $this->container->get(ConfigProviderInterface::class);

        return $config->get($key);
    }

    private function getFile($filename): string
    {
        $config = $this->container->get(FileDataReader::class);

        return $config->getContents($filename);
    }

    private function GetHashFromXml($xml): string
    {
        $utils = $this->container->get(XmlUtils::class);

        return $utils->getHashSign($xml);
    }

    /**
     * @param $result
     */
    private function toBase64Zip(BaseResult $result): void
    {
        if ($result->isSuccess() && !($result instanceof SummaryResult)) {
            /**@var $result BillResult */
            $zip = $result->getCdrZip();
            if ($zip) {
                $result->setCdrZip(base64_encode($zip));
            }
        }
    }
}
