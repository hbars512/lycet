<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 23:41
 */

namespace App\Controller\v1;

use App\Service\DocumentRequestXmlInterface;
use App\Service\SeeFactory;
use Greenter\Model\Voided\Voided;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class VoidedXmlController.
 *
 * @Route("/api/v1/voided")
 */
class VoidedXmlController extends AbstractController
{
    /**
     * @var DocumentRequestXmlInterface
     */
    private $document;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * InvoiceController constructor.
     * @param DocumentRequestXmlInterface $document
     * @param SerializerInterface $serializer
     */
    public function __construct(DocumentRequestXmlInterface $document, SerializerInterface $serializer)
    {
        $this->document = $document;
        $this->document->setDocumentType(Voided::class);
        $this->serializer = $serializer;
    }

    /**
     * @Route("/send_voided_xml", methods={"POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function send_voided_xml(Request $request): Response
    {
        return $this->document->send_voided_xml($request);
    }

    /**
     * @Route("/status", methods={"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request, SeeFactory $factory): JsonResponse
    {
        $ticket = $request->query->get('ticket');
        if (empty($ticket)) {
            return new JsonResponse(['message' => 'Ticket Requerido'], 400);
        }
        $see = $factory->build(Voided::class, $request->query->get('ruc'));
        $result = $see->getStatus($ticket);

        if ($result->isSuccess()) {
            $result->setCdrZip(base64_encode($result->getCdrZip()));
        }
        $json = $this->serializer->serialize($result, 'json');

        return new JsonResponse($json, 200, [], true);
    }
}