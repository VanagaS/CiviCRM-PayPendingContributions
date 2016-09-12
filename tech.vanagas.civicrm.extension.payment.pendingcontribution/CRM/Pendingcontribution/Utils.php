<?php

namespace tech\vanagas\civicrm\extension\payment\pendingcontribution {

    /**
     * Class Utils
     * @package tech\vanagas\civicrm\extension\payment\pendingcontribution
     */
    class Utils
    {
        static protected $_sortOrder;

        static function prepareSortableHeaders()
        {
            self::$_sortOrder =
                array(
                    array(
                        'name' => ts('Type'),
                        'sort' => 'financial_type',
                        'direction' => CRM_Utils_Sort::DONTCARE,
                    ),
                    array(
                        'name' => ts('Source'),
                        'sort' => 'contribution_source',
                        'direction' => CRM_Utils_Sort::DONTCARE,
                    ),
                    array(
                        'name' => ts('Received'),
                        'sort' => 'receive_date',
                        'direction' => CRM_Utils_Sort::DESCENDING,
                    ),
                    array(
                        'name' => ts('Thank-you Sent'),
                        'sort' => 'thankyou_date',
                        'direction' => CRM_Utils_Sort::DONTCARE,
                    ),
                    array(
                        'name' => ts('Status'),
                        'sort' => 'contribution_status',
                        'direction' => CRM_Utils_Sort::DONTCARE,
                    ),
                    array(
                        'name' => ts('Premium'),
                        'sort' => 'product_name',
                        'direction' => CRM_Utils_Sort::DONTCARE,
                    ),
                );
        }

    }
}