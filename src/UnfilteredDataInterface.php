<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\InputFilter;

/**
 * Ensures Inputs store unfiltered data and are capable of returning it
 */
interface UnfilteredDataInterface
{
    /**
     * @return array
     */
    public function getUnfilteredData();

    /**
     * @param array  $data
     *
     * @return array
     */
    public function setUnfilteredData($data);
}
