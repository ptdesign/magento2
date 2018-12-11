<?php
/**
 * Module for Magento 2 by Moloni
 * Copyright (C) 2017  Moloni, lda
 *
 * This file is part of Invoicing/Moloni.
 *
 * Invoicing/Moloni is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Invoicing\Moloni\Model;

use Invoicing\Moloni\Api\Data\TokensInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use \Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Framework\Api\SearchCriteriaInterface;
use Invoicing\Moloni\Api\Data\TokensSearchResultsInterface;
use Invoicing\Moloni\Api\TokensRepositoryInterface;
use Invoicing\Moloni\Model\ResourceModel\Tokens as ObjectResourceModel;
use Invoicing\Moloni\Model\ResourceModel\Tokens\Collection;
use Magento\Framework\Api\SearchResultsInterfaceFactory;

class TokensRepository
{
    public $objectFactory;

    public $objectResourceModel;

    public $collectionFactory;

    public $searchResultsFactory;

    public function __construct(
        ObjectResourceModel $objectResourceModel,
        TokensFactory $objectFactory,
        Collection $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    )
    {
        $this->objectFactory = $objectFactory;
        $this->objectResourceModel = $objectResourceModel;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @param \Invoicing\Moloni\Api\Data\TokensInterface $tokens
     * @return int
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(TokensInterface $tokens)
    {
        $this->objectResourceModel->save($tokens);
        return $tokens->getId();
    }

    /**
     * @param $tokenId
     * @return \Invoicing\Moloni\Api\Data\TokensInterface int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($tokenId)
    {
        $tokens = $this->objectFactory->create();
        $this->objectResourceModel->load($tokens, $tokenId);

        if (!$tokens->getId()) {
            throw new NoSuchEntityException(__('Tokens do not exist'));
        }

        return $tokens;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $collection = $this->collectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $fields[] = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $objects = [];
        foreach ($collection as $objectModel) {
            $objects[] = $objectModel;
        }
        $searchResults->setItems($objects);
        return $searchResults;
    }

    /**
     * @param int $tokenId
     * @return bool
     * @throws \Exception
     */
    public function delete(TokensInterface $tokens)
    {
        try {
            $this->objectFactory->delete($tokens);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function deleteById($id)
    {
        try {
            return $this->delete($this->getById($id));
        } catch (NoSuchEntityException $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
    }
}
