<?php

namespace SmartyStreets\PhpSdk\US_Autocomplete;

require_once(dirname(dirname(__FILE__)) . '/ArrayUtil.php');
require_once(dirname(dirname(__FILE__)) . '/Sender.php');
require_once(dirname(dirname(__FILE__)) . '/Serializer.php');
require_once(dirname(dirname(__FILE__)) . '/Request.php');
require_once('GeolocateType.php');
use SmartyStreets\PhpSdk\ArrayUtil;
use SmartyStreets\PhpSdk\Exceptions\SmartyException;
use SmartyStreets\PhpSdk\Sender;
use SmartyStreets\PhpSdk\Serializer;
use SmartyStreets\PhpSdk\Request;

class Client {
    private $sender,
        $serializer,
        $referer;

    public function __construct(Sender $sender, Serializer $serializer = null, $referer = null) {
        $this->sender = $sender;
        $this->serializer = $serializer;
        $this->referer = $referer;
    }

    public function sendLookup(Lookup $lookup) {
        if ($lookup == null || $lookup->getPrefix() == null || count($lookup->getPrefix()) == 0)
            throw new SmartyException("Send() must be passed a Lookup with the prefix field set.");

        $request = $this->buildRequest($lookup);

        $response = $this->sender->send($request);

        $result = $this->serializer->deserialize($response->getPayload());
        if ($result == null)
            return;

        $suggestions = $result->getSuggestions();
        if ($suggestions == null)
            return;

        $this->assignSuggestionsToLookup($lookup, $suggestions);
    }

    private function buildRequest(Lookup $lookup) {
        $request = new Request();

        $request->setParameter("prefix", $lookup->getPrefix());
        $request->setParameter("suggestions", strval($lookup->getMaxSuggestions()));
        $request->setParameter("city_filter", $this->buildFilterString($lookup->getCityFilter()));
        $request->setParameter("state_filter", $this->buildFilterString($lookup->getStateFilter()));
        $request->setParameter("prefer", $this->buildFilterString($lookup->getPrefer()));
        if ($lookup->getGeolocateType() != GEOLOCATE_TYPE_NONE) {
            $request->setParameter("geolocate", "true");
            $request->setParameter("geolocate_precision", $lookup->getGeolocateType()->getName());
        }
        else $request->setParameter("geolocate", "false");

        return $request;
    }

    private function buildFilterString($list) {
        if (empty($list))
            return null;

        $filterList = "";

        foreach ($list as $item)
            $filterList .= ($item . ',');

        if (ArrayUtil::endsWith($filterList, ','))
            $filterList = substr($filterList, 0, strlen($filterList) - 1);

        return $filterList;
    }

    private function assignSuggestionsToLookup(Lookup $lookup, $suggestions) {
        $result = array();

        foreach ($suggestions as $s) {
            $suggestion = new Suggestion($s);
            $result[] = $suggestion;
        }

        $lookup->setResult($result);
    }
}