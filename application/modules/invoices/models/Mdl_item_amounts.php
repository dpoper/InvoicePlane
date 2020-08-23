<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * InvoicePlane
 *
 * @author		InvoicePlane Developers & Contributors
 * @copyright	Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 */

/**
 * Class Mdl_Item_Amounts
 */
class Mdl_Item_Amounts extends CI_Model
{
    /**
     * item_amount_id
     * item_id
     * item_subtotal (item_quantity * item_price)
     * item_tax_total
     * item_total ((item_quantity * item_price) + item_tax_total)
     *
     * @param $item_id
     */
    public function calculate($item_id)
    {
        $this->load->model('invoices/mdl_items');
        $item = $this->mdl_items->get_by_id($item_id);
/*
 * Dirk Poper
 * IHMO totally incorrect calculation
 * wrong:		$item_subtotal = $item->item_quantity * $item->item_price;
 * should be:	$item_subtotal = $item->item_quantity * ($item->item_price - $item->item_discount_amount);
 * in this case $item_tax_total will be correct too.
 * wrong:		$item_discount_total = $item->item_discount_amount * $item->item_quantity;
 * should be:	$item_discount_total = $item->item_discount_amount * $item->item_quantity * ($item->item_tax_rate_percent / 100);
 * or better:	$item_discount_total = $item_subtotal * ($item->item_tax_rate_percent / 100) (if item_subtotal has ben changed to: $item_subtotal = $item->item_quantity * ($item->item_price - $item->item_discount_amount)
 * in this case item_total needs to be changed to $item_total = $item_subtotal + $item_tax_total
 * Beaware: Changes here will have impact on IP_INVOICE_AMOUNTS in Mdl_invoice_amounts.php
*/
        //Alt: $item_subtotal = $item->item_quantity * $item->item_price;
		$item_subtotal = $item->item_quantity * $item->item_price;
		//$item_subtotal = $item->item_quantity * ($item->item_price - $item->item_discount_amount);
		$item_subtotal_discounted = $item->item_quantity * ($item->item_price - $item->item_discount_amount);
        $item_tax_total = $item_subtotal_discounted * ($item->item_tax_rate_percent / 100);
        $item_discount_total = $item->item_discount_amount * $item->item_quantity;
        //Alt: $item_total = $item_subtotal + $item_tax_total - $item_discount_total;
		$item_total_gross = $item_subtotal + $item_tax_total;
		$item_total_net = $item_subtotal;

        $db_array = array(
            'item_id' => $item_id,
            'item_subtotal' => $item_subtotal,
			'item_subtotal_discounted' => $item_subtotal_discounted,
            'item_tax_total' => $item_tax_total,
            'item_discount_total' => $item_discount_total,
            'item_total_gross' => $item_total_gross,
            'item_total_net' => $item_total_net
        );

        $this->db->where('item_id', $item_id);
        if ($this->db->get('ip_invoice_item_amounts')->num_rows()) {
            $this->db->where('item_id', $item_id);
            $this->db->update('ip_invoice_item_amounts', $db_array);
        } else {
            $this->db->insert('ip_invoice_item_amounts', $db_array);
        }
    }

}
