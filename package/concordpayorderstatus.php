<?php
/**
 * PHP version 7.2.34
 *
 * @category   Class
 * @package    EShop
 * @subpackage ConcordPay
 * @author     ConcordPay <serhii.shylo@mustpay.tech>
 * @copyright  2022 ConcordPay
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://concordpay.concord.ua
 * @since      3.8.0
 */

// No direct access
defined('_JEXEC') or die();
jimport('joomla.form.formfield');

class JFormFieldConcordpayorderstatus extends JFormField
{
	/**
	 * Element name
	 *
	 * @var string
	 *
	 * @access protected
	 *
	 * @since 3.8.0
	 */
	var $_name = 'concordpayorderstatus';

	/**
	 * Return order statuses list
	 *
	 * @return mixed|string
	 *
	 * @since version 3.8.0
	 */
	function getInput()
	{
		/** @var  array $statuses */
		$statuses = self::getOrderStatusNames(JComponentHelper::getParams('com_languages')->get('site', 'en-GB'));

		$options = [];

		foreach ($statuses as $status)
		{
			$options[] = JHtml::_('select.option', $status->orderstatus_id, $status->orderstatus_name);
		}

		$orderStatus = JHtml::_('select.genericlist', $options, $this->name,
			array(
				'option.text.toHtml' => false,
				'option.value' => 'value',
				'option.text' => 'text',
				'list.attr' => ' class="inputbox"',
				'list.select' => $this->value)
		);

		return $orderStatus;
	}

	/**
	 * Function to get names of a order statuses.
	 *
	 * @param   string $langCode Current language code.
	 *
	 * @return string
	 *
	 * @since 3.8.0
	 */
	public static function getOrderStatusNames($langCode)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select(['orderstatus_id', 'orderstatus_name'])
			->from('#__eshop_orderstatusdetails')
			->where('language = "' . $langCode . '"');
		$db->setQuery($query);

		return $db->loadObjectList();
	}
}
