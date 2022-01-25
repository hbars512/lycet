<?php

/**
 * Created by Robot.
 * User: Own
 * Date: 20/01/2022
 * Time: 00:25
 */

namespace App\Controller\v1;
use App\Service\DocumentRequestXmlInterface;
use Greenter\Model\Sale\Invoice;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class InvoiceXmlController.
 *
 * @Route("/api/v1/invoice")
 */
class InvoiceXmlController extends AbstractController
{
    /**
     * @var DocumentRequestXmlInterface
     */
    private $document;

    /**
     * InvoiceXmlController constructor.
     * @param DocumentRequestXmlInterface $document
     */
    public function __construct(DocumentRequestXmlInterface $document)
    {
        $this->document = $document;
        $this->document->setDocumentType(Invoice::class);
    }

    /**
     * @Route("/send_xml", methods={"POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function send_xml(Request $request): Response
    {
        return $this->document->send_xml($request);
    }
}
