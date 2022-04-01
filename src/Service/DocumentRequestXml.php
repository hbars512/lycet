<?php
/**
 * Created by Robot.
 * User: Robot
 * Date: 20/01/2022
 * Time: 1:17
 */

namespace App\Service;
use Greenter\Model\Response\BaseResult;
use Greenter\Model\Response\SummaryResult;
use Greenter\Report\XmlUtils;
use Greenter\See;
use Psr\Container\ContainerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Class DocumentRequestXml
 */
class DocumentRequestXml implements DocumentRequestXmlInterface
{
    /**
     * @var string
     */
    private $className;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ContainerInterface $container)
    {
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
    public function send_xml(Request $request): Response
    {
        $document = json_decode($request->getContent());
        $filename = $document->filename;
        $ruc = $document->ruc;

        $file = $filename.'.xml';
        $dir_to_save = "./data_sunat/";
        $xmlSigned = file_get_contents($dir_to_save.$file);

        $see = $this->getSee($ruc);
        $result = $see->sendXmlFile($xmlSigned);

        if (!$result->isSuccess()) {
            $objeto = [
                "Codigo Error" => $result->getError()->getCode(),
                "Mensaje Error" => $result->getError()->getMessage()
            ];
            return $this->json($objeto, 400);
        }
        $dir_to_save = "./data_sunat/";
        if (!is_dir($dir_to_save)) {
            mkdir($dir_to_save);
        }
        file_put_contents($dir_to_save.'R-'.$filename.'.zip', $result->getCdrZip());

        $this->toBase64Zip($result);
        $xml = $see->getFactory()->getLastXml();

        $data = [
            'xml' => $xml,
            'hash' => $this->GetHashFromXml($xml),
            'sunatResponse' => $result
        ];

        return $this->json($data);
    }

    public function send_summary_xml(Request $request): Response
    {
        $document = json_decode($request->getContent());
        $filename = $document->filename;
        $ruc = $document->ruc;

        $file = $filename.'.xml';

        $dir_to_save = "./data_sunat/";
        $xmlSigned = file_get_contents($dir_to_save.$file);

        $see = $this->getSee($ruc);
        $result = $see->sendXmlFile($xmlSigned);

        if (!$result->isSuccess()) {
            $objeto = [
                "Codigo Error" => $result->getError()->getCode(),
                "Mensaje Error" => $result->getError()->getMessage()
            ];
            return $this->json($objeto, 400);
        }

        $this->toBase64Zip($result);
        $xml = $see->getFactory()->getLastXml();

        $data = [
            'xml' => $xml,
            'hash' => $this->GetHashFromXml($xml),
            'sunatResponse' => $result
        ];

        return $this->json($data);
    }
    public function send_voided_xml(Request $request): Response
    {
        $document = json_decode($request->getContent());
        $filename = $document->filename;
        $ruc = $document->ruc;

        $file = $filename.'.xml';

        $dir_to_save = "./data_sunat/";
        $xmlSigned = file_get_contents($dir_to_save.$file);

        $see = $this->getSee($ruc);
        $result = $see->sendXmlFile($xmlSigned);

        if (!$result->isSuccess()) {
            $objeto = [
                "Codigo Error" => $result->getError()->getCode(),
                "Mensaje Error" => $result->getError()->getMessage()
            ];
            return $this->json($objeto, 400);
        }

        $this->toBase64Zip($result);
        $xml = $see->getFactory()->getLastXml();

        $data = [
            'xml' => $xml,
            'hash' => $this->GetHashFromXml($xml),
            'sunatResponse' => $result
        ];

        return $this->json($data);
    }

    public function send_note_xml(Request $request): Response
    {
        $document = json_decode($request->getContent());
        $filename = $document->filename;
        $ruc = $document->ruc;

        $file = $filename.'.xml';

        $dir_to_save = "./data_sunat/";
        $xmlSigned = file_get_contents($dir_to_save.$file);

        $see = $this->getSee($ruc);
        $result = $see->sendXmlFile($xmlSigned);

        if (!$result->isSuccess()) {
            $objeto = [
                "Codigo Error" => $result->getError()->getCode(),
                "Mensaje Error" => $result->getError()->getMessage()
            ];
            return $this->json($objeto, 400);
        }

        $this->toBase64Zip($result);
        $xml = $see->getFactory()->getLastXml();

        $data = [
            'xml' => $xml,
            'hash' => $this->GetHashFromXml($xml),
            'sunatResponse' => $result
        ];

        return $this->json($data);
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

    private function json($data, int $status = 200, array $headers = [])
    {
        $json = $this->container->get('serializer')->serialize($data, 'json');

        return new JsonResponse($json, $status, $headers, true);
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
