<?php

namespace Reliv\PipeRat\Middleware\ResponseFormat;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Reliv\PipeRat\Exception\ResponseFormatException;
use Reliv\PipeRat\Middleware\Middleware;

/**
 * Class XmlResponseFormat
 *
 * @author    James Jervis <jjervis@relivinc.com>
 * @copyright 2016 Reliv International
 * @license   License.txt
 * @link      https://github.com/reliv
 */
class XmlResponseFormat extends AbstractResponseFormat implements Middleware
{
    /**
     * @var array
     */
    protected $defaultAcceptTypes = [
        'application/xml'
    ];

    /**
     * arrayToXml
     *
     * @param array             $data
     * @param \SimpleXMLElement $xml_data
     *
     * @return void
     */
    protected function arrayToXml(array $data, \SimpleXMLElement &$xml_data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                //dealing with <0/>..<n/> issues
                if (is_numeric($key)) {
                    $key = 'item' . $key;
                }
                $subNode = $xml_data->addChild($key);
                $this->arrayToXml($value, $subNode);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    /**
     * __invoke
     *
     * @param Request       $request
     * @param Response      $response
     * @param callable|null $next
     *
     * @return static
     * @throws ResponseFormatException
     */
    public function __invoke(
        Request $request,
        Response $response,
        callable $next = null
    ) {
        $response = $next($request);

        if (!$this->isFormattableResponse($response)) {
            return $response;
        }

        if (!$this->isValidAcceptType($request)) {
            return $response;
        }

        $dataModel = $this->getDataModel($response, null);

        if(!is_array($dataModel)) {
            throw new ResponseFormatException(get_class($this) . ' requires dataModel to be an array');
        }

        $body = $response->getBody();

        $xmlData = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');

        $content = null;

        if (is_array($dataModel)) {
            $this->arrayToXml($dataModel, $xmlData);

            $content = $xmlData->asXML();
        }

        $body->rewind();
        $body->write($content);

        return $response->withBody($body)->withHeader(
            'Content-Type',
            'application/xml'
        );
    }
}
