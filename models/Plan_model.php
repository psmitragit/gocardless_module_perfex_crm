<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Plan_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($id = false)
    {
        $id ? $this->db->where('id', $id) : "";
        $this->db->order_by('name', 'ASC');
        $plans = $this->db->get(db_prefix() . 'subscription_plans');

        return $id ? $plans->row() : $plans->result();
    }
}
