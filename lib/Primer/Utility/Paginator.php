<?php
/*
 * PHP Pagination Class
 *
 * @author David Carr - dave@daveismyname.com - http://www.daveismyname.com
 * https://github.com/daveismyname/pagination
 * @version 1.0
 * @date October 20, 2012
 */

namespace Primer\Utility;

use Primer\Core\Object;

class Paginator extends Object
{

    /**
     * set the number of items per page.
     *
     * @var numeric
     */
    private $_perPage;

    /**
     * set get parameter for fetching the page number
     *
     * @var string
     */
    private $_instance;

    /**
     * Set the query string to preserve in pagination links
     *
     * @var
     */
    private $_queryString;

    /**
     * sets the page number.
     *
     * @var numeric
     */
    private $_page;

    /**
     * set the limit for the data source
     *
     * @var string
     */
    private $_limit;

    /**
     * set the total number of records/items.
     *
     * @var numeric
     */
    private $_totalRows = 0;


    /**
     * Construct paginator given params config
     *
     * @param $params
     */
    public function __construct($params)
    {
        $params = array_merge(
            array(
                'perPage'  => 5,
                'instance' => 'p',
                'query'    => 'q',
            ),
            $params
        );

        $this->_perPage = $params['perPage'];
        $this->_instance = $params['instance'];
        $this->_queryString = $params['query'];

        $this->set_instance();
    }

    /**
     * set_instance
     *
     * sets the instance parameter, if numeric value is 0 then set to 1
     *
     * @var numeric
     */
    private function set_instance()
    {
        $this->_page = (int)(!isset($_GET[$this->_instance]) ? 1 : $_GET[$this->_instance]);
        $this->_page = ($this->_page == 0 ? 1 : $this->_page);
    }

    /**
     * set_total
     *
     * collect a numberic value and assigns it to the totalRows
     *
     * @var numeric
     */
    public function set_total($_totalRows)
    {
        $this->_totalRows = $_totalRows;
    }

    /**
     * get_limit
     *
     * returns the limit for the data source, calling the get_start method and passing in the number of items perp page
     *
     * @return string
     */
    public function get_limit()
    {
        return $this->get_start() . ",$this->_perPage";
    }

    /**
     * get_start
     *
     * creates the starting point for limiting the dataset
     *
     * @return numeric
     */
    private function get_start()
    {
        return ($this->_page * $this->_perPage) - $this->_perPage;
    }

    /**
     * Create the HTML links for navigating through the dataset
     *
     * @param string $path optionally set the path for the link
     * @param null   $ext  optionally pass in extra parameters to the GET
     *
     * @return string HTML markup
     */
    public function page_links($path = '?', $ext = null)
    {
        $adjacents = "2";
        $prev = $this->_page - 1;
        $next = $this->_page + 1;
        $lastpage = ceil($this->_totalRows / $this->_perPage);
        $lpm1 = $lastpage - 1;

        $pagination = "";
        if ($lastpage > 1) {
            $pagination .= "<div class='pagination'>";
            if ($this->_page > 1) {
                $pagination .= "<a href='" . $path . "$this->_instance=$prev"
                    . "$ext'>« previous</a>";
            }
            else {
                $pagination .= "<span class='disabled'>« previous</span>";
            }

            if ($lastpage < 7 + ($adjacents * 2)) {
                for ($counter = 1; $counter <= $lastpage; $counter++) {
                    if ($counter == $this->_page) {
                        $pagination .= "<span class='current'>$counter</span>";
                    }
                    else {
                        $pagination .= "<a href='" . $path . "$this->_instance=$counter"
                            . "$ext'>$counter</a>";
                    }
                }
            }
            elseif ($lastpage > 5 + ($adjacents * 2)) {
                if ($this->_page < 1 + ($adjacents * 2)) {
                    for (
                        $counter = 1; $counter < 4 + ($adjacents * 2);
                        $counter++
                    ) {
                        if ($counter == $this->_page) {
                            $pagination .= "<span class='current'>$counter</span>";
                        }
                        else {
                            $pagination
                                .= "<a href='" . $path . "$this->_instance=$counter"
                                . "$ext'>$counter</a>";
                        }
                    }
                    $pagination .= "...";
                    $pagination .= "<a href='" . $path . "$this->_instance=$lpm1"
                        . "$ext'>$lpm1</a>";
                    $pagination .= "<a href='" . $path . "$this->_instance=$lastpage"
                        . "$ext'>$lastpage</a>";
                }
                elseif ($lastpage - ($adjacents * 2) > $this->_page
                    && $this->_page > ($adjacents * 2)
                ) {
                    $pagination .= "<a href='" . $path . "$this->_instance=1"
                        . "$ext'>1</a>";
                    $pagination .= "<a href='" . $path . "$this->_instance=2"
                        . "$ext'>2</a>";
                    $pagination .= "...";
                    for (
                        $counter = $this->_page - $adjacents;
                        $counter <= $this->_page + $adjacents; $counter++
                    ) {
                        if ($counter == $this->_page) {
                            $pagination .= "<span class='current'>$counter</span>";
                        }
                        else {
                            $pagination
                                .= "<a href='" . $path . "$this->_instance=$counter"
                                . "$ext'>$counter</a>";
                        }
                    }
                    $pagination .= "..";
                    $pagination .= "<a href='" . $path . "$this->_instance=$lpm1"
                        . "$ext'>$lpm1</a>";
                    $pagination .= "<a href='" . $path . "$this->_instance=$lastpage"
                        . "$ext'>$lastpage</a>";
                }
                else {
                    $pagination .= "<a href='" . $path . "$this->_instance=1"
                        . "$ext'>1</a>";
                    $pagination .= "<a href='" . $path . "$this->_instance=2"
                        . "$ext'>2</a>";
                    $pagination .= "..";
                    for (
                        $counter = $lastpage - (2 + ($adjacents * 2));
                        $counter <= $lastpage; $counter++
                    ) {
                        if ($counter == $this->_page) {
                            $pagination .= "<span class='current'>$counter</span>";
                        }
                        else {
                            $pagination
                                .= "<a href='" . $path . "$this->_instance=$counter"
                                . "$ext'>$counter</a>";
                        }
                    }
                }
            }

            if ($this->_page < $counter - 1) {
                $pagination .= "<a href='" . $path . "$this->_instance=$next"
                    . "$ext'>next »</a>";
            }
            else {
                $pagination .= "<span class='disabled'>next »</span>";
            }
            $pagination .= "</div>\n";
        }


        return "<div class='page-links'>$pagination</div>";
    }

    /**
     * Create the HTML links for navigating through the dataset
     *
     * @param string $path optionally set the path for the link
     * @param null   $ext  optionally pass in extra parameters to the GET
     *
     * @return string HTML markup
     */
    public function page_links_list($path = '?', $ext = null)
    {
        $adjacents = "2";
        $prev = $this->_page - 1;
        $next = $this->_page + 1;
        $lastpage = ceil($this->_totalRows / $this->_perPage);
        $lpm1 = $lastpage - 1;

        if (isset($_GET[$this->_queryString])) {
            $path .= "{$this->_queryString}={$_GET[$this->_queryString]}&";
        }

        $pagination = "";
        if ($lastpage > 1) {
            $pagination .= "<ul class='pagination'>";
            if ($this->_page > 1) {
                $pagination .= "<li><a href='" . $path . "$this->_instance=$prev"
                    . "$ext'>«</a></li>";
            }
            else {
                $pagination .= "<li><a href=\"#\">«</a></li>";
            }

            if ($lastpage < 7 + ($adjacents * 2)) {
                for ($counter = 1; $counter <= $lastpage; $counter++) {
                    if ($counter == $this->_page) {
                        $pagination .= "<li><a href=\"#\">$counter</a></li>";
                    }
                    else {
                        $pagination
                            .= "<li><a href='" . $path . "$this->_instance=$counter"
                            . "$ext'>$counter</a></li>";
                    }
                }
            }
            elseif ($lastpage > 5 + ($adjacents * 2)) {
                if ($this->_page < 1 + ($adjacents * 2)) {
                    for (
                        $counter = 1; $counter < 4 + ($adjacents * 2);
                        $counter++
                    ) {
                        if ($counter == $this->_page) {
                            $pagination .= "<li><a href=\"#\">$counter</a></li>";
                        }
                        else {
                            $pagination
                                .= "<li><a href='" . $path . "$this->_instance=$counter"
                                . "$ext'>$counter</a></li>";
                        }
                    }
                    $pagination .= "...";
                    $pagination .= "<li><a href='" . $path . "$this->_instance=$lpm1"
                        . "$ext'>$lpm1</a></li>";
                    $pagination .= "<li><a href='" . $path . "$this->_instance=$lastpage"
                        . "$ext'>$lastpage</a></li>";
                }
                elseif ($lastpage - ($adjacents * 2) > $this->_page
                    && $this->_page > ($adjacents * 2)
                ) {
                    $pagination .= "<li><a href='" . $path . "$this->_instance=1"
                        . "$ext'>1</a></li>";
                    $pagination .= "<li><a href='" . $path . "$this->_instance=2"
                        . "$ext'>2</a></li>";
                    $pagination .= "...";
                    for (
                        $counter = $this->_page - $adjacents;
                        $counter <= $this->_page + $adjacents; $counter++
                    ) {
                        if ($counter == $this->_page) {
                            $pagination .= "<li><a href=\"#\">$counter</a></li>";
                        }
                        else {
                            $pagination
                                .= "<li><a href='" . $path . "$this->_instance=$counter"
                                . "$ext'>$counter</a></li>";
                        }
                    }
                    $pagination .= "..";
                    $pagination .= "<li><a href='" . $path . "$this->_instance=$lpm1"
                        . "$ext'>$lpm1</a></li>";
                    $pagination .= "<li><a href='" . $path . "$this->_instance=$lastpage"
                        . "$ext'>$lastpage</a></li>";
                }
                else {
                    $pagination .= "<li><a href='" . $path . "$this->_instance=1"
                        . "$ext'>1</a></li>";
                    $pagination .= "<li><a href='" . $path . "$this->_instance=2"
                        . "$ext'>2</a></li>";
                    $pagination .= "..";
                    for (
                        $counter = $lastpage - (2 + ($adjacents * 2));
                        $counter <= $lastpage; $counter++
                    ) {
                        if ($counter == $this->_page) {
                            $pagination .= "<li><a href=\"#\">$counter</a></li>";
                        }
                        else {
                            $pagination
                                .= "<li><a href='" . $path . "$this->_instance=$counter"
                                . "$ext'>$counter</a></li>";
                        }
                    }
                }
            }

            if ($this->_page < $counter - 1) {
                $pagination .= "<li><a href='" . $path . "$this->_instance=$next"
                    . "$ext'>»</a></li>";
            }
            else {
                $pagination .= "<li><a href=\"#\">»</a></li>";
            }
            $pagination .= "</ul>\n";
        }

        return "<div class='page-links'>$pagination</div>";
    }
}