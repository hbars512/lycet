<?php
/**
 * Created by Robot.
 * User: Robot
 * Date: 20/01/2022
 * Time: 1:17
 */

namespace App\Service;

use Greenter\See;
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Interface DocumentRequestXmlInterface
 */
interface DocumentRequestXmlInterface
{
    /**
     * Set document to process.
     *
     * @param string $class
     */
    public function setDocumentType(string $class);

    /**
     * Get Result.
     *
     * @param Request $request
     * @return Response
     */
    public function send_xml(Request $request): Response;

    /**
     * Get Result.
     *
     * @param Request $request
     * @return Response
     */
    public function send_summary_xml(Request $request): Response;

        /**
     * Get Result.
     *
     * @param Request $request
     * @return Response
     */
    public function send_voided_xml(Request $request): Response;

    /**
     * Get Result.
     *
     * @param Request $request
     * @return Response
     */
    public function send_note_xml(Request $request): Response;

    /**
     * Get Configured See.
     *
     * @param string $ruc
     * @return See
     */
    public function getSee(string $ruc): See;
}
