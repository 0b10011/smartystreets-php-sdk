<?php

namespace smartystreets\api\us_street;

require_once(dirname(dirname(dirname(__FILE__))) . '/api/Sender.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/api/Serializer.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/api/Request.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/api/Batch.php');
use smartystreets\api\Sender;
use smartystreets\api\Serializer;
use smartystreets\api\Request;
use smartystreets\api\Batch;

class Client {
    private $urlPrefix,
            $sender,
            $serializer,
            $referer;

    public function __construct($urlPrefix, Sender $sender, Serializer $serializer = null, $referer = null) {
        $this->urlPrefix = $urlPrefix;
        $this->sender = $sender;
        $this->serializer = $serializer;
        $this->referer = $referer;
    }

    public function sendLookup(Lookup $lookup) {
        $batch = new Batch();
        $batch->add($lookup);
        $this->sendBatch($batch);
    }

    public function sendBatch(Batch $batch) {
        $request = new Request($this->urlPrefix);

        if ($batch->size() == 0)
            return;

        $request->setPayload($this->serializer->serialize($batch->getAllLookups()));
        $request->setReferer($this->referer);

        $response = $this->sender->send($request);

        $results = $this->serializer->deserialize($response->getPayload());
        if ($results == null)
            $results = array();

        $this->assignResultsToLookups($batch, $results);
    }

    private function assignResultsToLookups(Batch $batch, $results) {
        foreach ($results as $rawResult) {
            $result = new Candidate($rawResult);
            $batch->getLookupByIndex($result->getInputIndex())->setResult($result);
        }
    }
}