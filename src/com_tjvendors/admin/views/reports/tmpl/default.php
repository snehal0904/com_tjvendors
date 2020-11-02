<?php
/**
 * @version    SVN:
 * @package    Com_Tjvendors
 * @author     Techjoomla  <contact@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

// Import CSS
$document = Factory::getDocument();
$document->addStyleSheet(Uri::root() . 'administrator/components/com_tjvendors/assets/css/tjvendors.css');

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_tjvendors');
$input     = Factory::getApplication()->input;
$client    = $input->get('client', '', 'STRING');
?>
<script type="text/javascript">
	var client="<?php echo $client; ?>";
	jQuery(document).ready(function ()
	{
		jQuery('#clear-search-button').on('click', function ()
		{
			jQuery('#filter_search').val('');
			jQuery('#adminForm').submit();
		});
	});

	jQuery(document).ready(function ()
	{
		jQuery('#clear-calendar').on('click', function ()
		{
			jQuery('#date').val('');
			jQuery('#dates').val('');
			jQuery('#adminForm').submit();
		});
	});

	Joomla.submitbutton = function (task)
	{
		if(task == "back")
		{
			window.location = "index.php?option=com_tjvendors&view=vendors&client="+client;
		}
	}

	var client = '<?php echo $client;?>';
</script>
<script type="text/javascript">
	tjVAdmin.reports.initReportsJs();
</script>
<?php
if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php
}
else
{
?>
	<div id="j-main-container">
<?php
}
?>
<form action="<?php echo Route::_('index.php?option=com_tjvendors&view=reports&vendor_id=' . $this->input->get('vendor_id', '', 'STRING') . '&client=' . $this->input->get('client', '', 'STRING')); ?>"
method="post" name="adminForm" id="adminForm">
<?php
if (!empty($this->sidebar))
{
?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="">
<?php
}
else
{
?>
	<div id="j-main-container">
<?php
}?>
<?php
	if (empty($this->items))
	{
		?>
			<div class="alert alert-no-items"><?php echo Text::_('COM_TJVENDOR_NO_MATCHING_RESULTS');?></div>
		<?php
	}
	?>
	<div id="filter-bar" class="btn-toolbar">
		<div class="filter-search btn-group pull-left">
			<label for="filter_search" class="element-invisible">
				<?php echo Text::_('JSEARCH_FILTER'); ?>
			</label>
			<input type="text" name="filter_search" id="filter_search"
				placeholder="<?php echo Text::_('COM_TJVENDOR_PAYOUTS_SEARCH_BY_VENDOR_TITLE'); ?>"
				value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
				title="<?php echo Text::_('JSEARCH_FILTER'); ?>"/>
		</div>
		<div class="btn-group pull-left">
			<button class="btn hasTooltip" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
				<i class="icon-search"></i>
			</button>
			<button class="btn hasTooltip" id="clear-search-button" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
				<i class="icon-remove"></i>
			</button>
		</div>
		<div class="btn-group pull-left hidden-phone">
			<?php
				echo JHTML::_('calendar', $this->state->get('filter.fromDate'), 'fromDates', 'dates', '%Y-%m-%d', array( 'class' => 'inputbox', 'onchange' => 'document.adminForm.submit()'));
			?>
		</div>
		<div class="btn-group pull-left hidden-phone">
			<?php
				echo JHTML::_('calendar', $this->state->get('filter.toDate'), 'toDates', 'date', '%Y-%m-%d', array( 'class' => 'inputbox', 'onchange' => 'document.adminForm.submit()'));
			?>
		</div>
		<div class="btn-group pull-left hidden-phone">
			<button class="btn hasTooltip" id="clear-calendar" type="button" title="<?php echo Text::_('JSEARCH_CALENDAR_CLEAR'); ?>">
				<i class="icon-remove"></i>
			</button>
		</div>
	</div>

	<div class="row-fluid btn-group pull-left hidden-phone">
		<div class="btn-group hidden-phone">
			<label for="limit" class="element-invisible">
				<?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?>
			</label>
			<?php echo $this->pagination->getLimitBox(); ?>
		</div>
		<div class="btn-group hidden-phone">
			<?php echo HTMLHelper::_('select.genericlist', $this->uniqueClients, "vendor_client", 'class="input-medium" size="1" onchange="document.adminForm.submit();"', "client_value", "vendor_client", $this->state->get('filter.vendor_client'));
			echo $filterClient = $this->state->get('filter.vendor_client');?>
		</div>
		<div class="btn-group hidden-phone">
			<?php
			$transactionType[] = array("transactionType" => Text::_('COM_TJVENDORS_REPORTS_FILTER_ALL_TRANSACTIONS'), "transactionValue" => "0");
			$transactionType[] = array("transactionType" => Text::_('COM_TJVENDORS_REPORTS_FILTER_CREDIT'), "transactionValue" => Text::_('COM_TJVENDORS_REPORTS_FILTER_CREDIT'));
			$transactionType[] = array("transactionType" => Text::_('COM_TJVENDORS_REPORTS_FILTER_DEBIT'), "transactionValue" => Text::_('COM_TJVENDORS_REPORTS_FILTER_DEBIT'));
			echo HTMLHelper::_('select.genericlist', $transactionType, "transactionType", 'class="input-medium" size="1" onchange="document.adminForm.submit();"', "transactionValue", "transactionType", $this->state->get('filter.transactionType'));?>
		</div>
		<div class="btn-group hidden-phone">
			<?php
				// Making custom filter list
				$this->currencies = TjvendorsHelper::getCurrencies($this->state->get('filter.vendor_id'));
				$currencyList[] = Text::_('JFILTER_PAYOUT_CHOOSE_CURRENCY');

				if ($this->currencies !== false)
				{
					foreach ($this->currencies as $currency)
					{
						$currencyList[] = $currency;
					}
				}

			echo HTMLHelper::_('select.genericlist', $currencyList, "currency", 'class="input-medium" size="1" onchange="document.adminForm.submit();"', "currency", "currency", $this->state->get('filter.currency'));?>
		</div>
		<div class="btn-group hidden-phone">
			<?php echo HTMLHelper::_('select.genericlist', $this->vendor_details, "vendor_id", 'class="input-medium" size="1" onchange="document.adminForm.submit();"', "vendor_id", "vendor_title", $this->state->get('filter.vendor_id'));?>
		</div>
	</div>
	<div class="pull-left alert alert-info">
		<?php echo Text::_('COM_TJVENDORS_REPORTS_CREDIT_NOTE');?>
		<?php echo Text::_('COM_TJVENDORS_REPORTS_DEBIT_NOTE'); ?>
	</div>
	</div>
	<?php
	if (!empty($this->items))
	{
		?>
		<table class="table table-striped table-responsive" >
			<thead>
				<tr></tr>
				<tr>
					<?php if (isset($this->items[0]->ordering)): ?>
					<th width="1%" class="nowrap center hidden-phone">
						<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.`ordering`', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
					</th>
					<?php endif; ?>

					<?php if (isset($this->items[0]->state)){} ?>
					<th class='left' width="10%">
						<?php echo HTMLHelper::_('grid.sort', 'COM_TJVENDORS_REPORTS_TRANSACTION_ID', 'pass.`transaction_id`', $listDirn, $listOrder); ?>
					</th>
					<th class='left' width="8%">
						<?php echo HTMLHelper::_('grid.sort', 'COM_TJVENDORS_PAYOUTS_PAYOUT_TITLE', 'vendors.`vendor_title`', $listDirn, $listOrder); ?>
					</th>
					<?php
						$filterClient = $this->state->get('filter.vendor_client');
						if(empty($filterClient))
						{?>
							<th class='left' width="5%">
								<?php echo HTMLHelper::_('grid.sort', 'COM_TJVENDORS_REPORTS_CLIENT', 'vendors.`vendor_client`', $listDirn, $listOrder); ?>
							</th>
					<?php
						}

						$filterCurrency = $this->state->get('filter.currency');
						if(empty($filterCurrency))
						{?>
							<th class='left' width="5%">
							<?php echo HTMLHelper::_('grid.sort', 'COM_TJVENDORS_PAYOUTS_CURRENCY', 'pass.`currency`', $listDirn, $listOrder); ?>
							</th>
					<?php
						}

						$transactionType = $this->state->get('filter.transactionType');

						if($transactionType == "credit" || empty($transactionType))
						{?>
							<th class='left' width="10%">
								<?php echo HTMLHelper::_('grid.sort', 'COM_TJVENDORS_REPORTS_CREDIT_AMOUNT', 'pass.`credit`', $listDirn, $listOrder);?>
							</th>
					<?php
						}

						if($transactionType == "debit" || empty($transactionType))
						{?>
							<th class='left' width="10%">
								<?php	echo HTMLHelper::_('grid.sort', 'COM_TJVENDORS_REPORTS_DEBIT_AMOUNT', 'pass.`debit`', $listDirn, $listOrder);?>
							</th>
					<?php
						}
					?>
					<th class='left' width="12%">
						<?php echo HTMLHelper::_('grid.sort', 'COM_TJVENDORS_REPORTS_REFERENCE_ORDER_ID', 'pass.`reference_order_id`', $listDirn, $listOrder); ?>
					</th>
					<th class='left' width="10%">
						<?php echo HTMLHelper::_('grid.sort', 'COM_TJVENDORS_PAYOUTS_PAYABLE_AMOUNT', 'pass.`total`', $listDirn, $listOrder); ?>
					</th>
					<th class='left' width="10%">
						<?php echo HTMLHelper::_('grid.sort', 'COM_TJVENDORS_REPORTS_TRANSACTION_TIME', 'pass.`transaction_time`', $listDirn, $listOrder); ?>
					</th>
					<th class='left' width="10%">
						<?php echo Text::_('COM_TJVENDORS_REPORTS_CUSTOMER_NOTE'); ?>
					</th>
					<th class='left' width="10%">
						<?php echo Text::_('COM_TJVENDORS_REPORTS_STATUS'); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<td colspan="5">
					<?php if($filterCurrency != '0'):?>
						<div class="pull-right">
							<tr>
								<th colspan="8"></th>
								<th colspan="12">
										<?php echo Text::_('COM_TJVENDORS_REPORTS_TOTAL_CREDIT_AMOUNT') . '&nbsp:&nbsp&nbsp ' . $this->totalDetails['creditAmount'] . '&nbsp' . $filterCurrency;?>
								</th>
							</tr>
							<tr>
								<th colspan="8"></th>
								<th colspan="12">
										<?php echo Text::_('COM_TJVENDORS_REPORTS_TOTAL_DEBIT_AMOUNT') . '&nbsp:&nbsp&nbsp ' . $this->totalDetails['debitAmount'] . '&nbsp' . $filterCurrency;?>
								</th>
							</tr>
							<tr>
								<th colspan="8"></th>
								<th colspan="12">
										<?php echo Text::_('COM_TJVENDORS_REPORTS_TOTAL_PENDING_AMOUNT') . '&nbsp:&nbsp&nbsp ' . $this->totalDetails['pendingAmount'] . '&nbsp' . $filterCurrency?>
								</th>
							</tr>
						</div>
					<?php endif;?>
				</td>
				<td colspan="5">
					<div class="pull-left">
						<?php echo $this->pagination->getListFooter();?>
					</div>
				</td>
			</tfoot>
			<tbody>
				<?php
				$options[]   = array("type" => Text::_('COM_TJVENDORS_STATUS_PAID'), "value" => "1");
				$options[]   = array("type" => Text::_('COM_TJVENDORS_STATUS_UNPAID'), "value" => "0");
				$doneOptions = Text::_('COM_TJVENDORS_STATUS_CREDIT_DONE');

				foreach ($this->items as $i => $item)
				{
					?>
					<tr>
						<td><?php echo $item->transaction_id;?></td>
						<td><?php echo $this->escape($item->vendor_title);?></td>
						<?php 
						if (empty($filterClient))
						{
							?>
							<td><?php echo TjvendorFrontHelper::getClientName($this->client);?>	</td>
							<?php 
						}

						if (empty($filterCurrency))
						{
							?>
							<td><?php echo $item->currency; ?></td>
							<?php 
						}

						if ($transactionType == "credit" || empty($transactionType))
						{
							?>
							<td>
								<?php
								if($item->credit <='0')
								{
									echo "0";
								}
								else
								{
									echo $item->credit;
								}
								?>
							</td>
						<?php
						}

						if($transactionType == "debit" || empty($transactionType))
						{
						?>
							<td><?php echo $item->debit;?></td>
						<?php
						}
						?>
						<td><?php echo $item->reference_order_id;?></td>
						<td><?php echo $item->total;?></td>
						<td><?php echo $item->transaction_time;?></td>
						<?php
						$status = json_decode($item->params, true);?>
						<td class="center">
							<?php
								if(!empty($status['customer_note']))
								{
									echo $status['customer_note'];
								}
								else
								{
									echo "-";
								}
							?>
						</td>
						<td>
					<?php
						if ($status['entry_status'] == "debit_payout")
						{
							echo JHTML::_('select.genericlist', $options, "paidUnpaid", 'class="input-medium" size="1" onChange="tjVAdmin.vendor.changePayoutStatus(' . $item->id . ',this);"', 'value', 'type', $item->status);
						}
						elseif ($status['entry_status'] == "credit_for_ticket_buy")
						{
							?>
							<select disabled class="span12">
								<option value=""><?php echo $doneOptions; ?></option>
							</select>
							<?php
						}
						?>
					</td>
					</tr>
				<?php
				}?>
			</tbody>
		</table>
		<?php
	}
		?>
		<input type="hidden" name="boxchecked" value="0"/>
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
