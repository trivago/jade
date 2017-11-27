<?php

namespace JsonApi;

use Trivago\Jade\Domain\Resource\Constraint;

class Meta implements \JsonSerializable
{
    private $resourceName;
    private $perPage;
    private $currentPage;
    private $count = 0;

    private function __construct(){}

    /**
     * @param $resourceName
     * @return Meta
     */
    public static function create($resourceName)
    {
        $meta = new self();
        $meta->resourceName = $resourceName;

        return $meta;
    }

    public function entityAdded()
    {
        $this->count++;
    }

    public function entityRemoved()
    {
        $this->count--;
    }

    /**
     * @param int $perPage
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * @param int $currentPage
     * @return $this
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTotalItems()
    {
        return $this->count;
    }

    /**
     * @return mixed
     */
    public function getTotalPages()
    {
        return (int) (abs($this->count-1)/$this->getPerPage() + 1);
    }

    public function getPerPage()
    {
        return $this->perPage ?: Constraint::DEFAULT_PER_PAGE;
    }

    public function getCurrentPage()
    {
        return $this->currentPage ?: Constraint::DEFAULT_PAGE_NUMBER;
    }

    public function isPerPageSet()
    {
        return null !== $this->perPage;
    }

    public function isCurrentPageSet()
    {
        return null !== $this->currentPage;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $data = [
            'perPage' => $this->getPerPage(),
            'currentPage' => $this->getCurrentPage(),
        ];

        if (Collection::$fetchTotalCount) {
            $data = array_merge($data, [
                'totalItems' => $this->getTotalItems(),
                'totalPages' => $this->getTotalPages(),
            ]);
        }

        return $data;
    }
}
