<?php

namespace JsonApi;

use Symfony\Component\HttpFoundation\JsonResponse;

class CollectionLinks
{
    /** @var string */
    private $type;

    /** @var Meta */
    private $meta;

    /** @var array */
    private $filter;

    private function __construct(){}

    public static function create($type, Meta $meta, array $filter)
    {
        $collectionLinks = new self();
        $collectionLinks->type = $type;
        $collectionLinks->meta = $meta;
        $collectionLinks->filter = $filter;

        return $collectionLinks;
    }

    public function first()
    {
        return $this->getCollectionUrl().'?'.$this->getCollectionQuery(1);
    }

    public function self()
    {
        return $this->getCollectionUrl().'?'.$this->getCollectionQuery($this->meta->getCurrentPage());
    }

    public function last()
    {
        return $this->getCollectionUrl().'?'.$this->getCollectionQuery($this->meta->getTotalPages());
    }

    private function getCollectionUrl()
    {
        return LinkBuilder::BASE_URL.'/'.$this->type;
    }

    private function getCollectionQuery($pageNumber)
    {
        $data = [];

        if ($this->filter) {
            $data['filter'] = \json_encode($this->filter);
        }

        $data['page'] = [
            'number' => $pageNumber,
        ];

        if ($this->meta->isPerPageSet()) {
            $data['page']['size'] = $this->meta->getPerPage();
        }

        return urldecode(http_build_query($data));
    }
}
