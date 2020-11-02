<?php
/**
 * @package     TJVendors
 * @subpackage  com_tjvendors
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

// Import CSS
$document = Factory::getDocument();
HTMLHelper::stylesheet('administrator/components/com_tjvendors/assets/css/tjvendors.css');
HTMLHelper::script('libraries/techjoomla/assets/js/houseKeeping.js');
$document->addScriptDeclaration("var tjHouseKeepingView='vendors';");

$user      = Factory::getUser();
$userId    = $user->id;
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_tjvendors');
$saveOrder = $listOrder == 'a.`ordering`';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjvendors&task=vendors.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'vendorList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>

<script type="text/javascript">
	Joomla.orderTable = function ()
	{
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>')
		{
			dirn = 'asc';
		}
		else
		{
			dirn = direction.options[direction.selectedIndex].value;
		}

		Joomla.tableOrdering(order, dirn, '');
	};

	jQuery(document).ready(function ()
	{
		jQuery('#clear-search-button').on('click', function ()
		{
			jQuery('#filter_search').val('');
			jQuery('#adminForm').submit();
		});
	});

	window.toggleField = function (id, task, field)
	{
		var f = document.adminForm,
			i = 0, cbx,
			cb = f[ id ];

		if (!cb) return false;

		while (true)
		{
			cbx = f[ 'cb' + i ];

			if (!cbx) break;

			cbx.checked = false;
			i++;
		}

		var inputField   = document.createElement('input');
		inputField.type  = 'hidden';
		inputField.name  = 'field';
		inputField.value = field;
		f.appendChild(inputField);

		cb.checked = true;
		f.boxchecked.value = 1;
		window.submitform(task);

		return false;
	};

	Joomla.submitbutton = function (task)
	{
		if(task == 'vendors.delete')
		{
			var msg = "<?php echo Text::_('COM_TJVENDORS_CONFIRM_TO_DELETE_RECORD'); ?>";

			if (confirm(msg) == true)
			{
				Joomla.submitform(task, document.getElementById('adminForm'));
			}
			else
			{
				return false;
			}
		}
		else
		{
			Joomla.submitform(task, document.getElementById('adminForm'));
		}
	}

</script>
<script type="text/javascript">
	var client = "<?php echo $this->input->get('client', '', 'STRING'); ?>";
</script>
<?php

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}

?>
<form
action="<?php echo Route::_('index.php?option=com_tjvendors&view=vendors&client=' . $this->input->get('client', '', 'STRING')); ?>" method="post" name="adminForm" id="adminForm">
<?php
if (!empty($this->sidebar))
{?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php
}
else
{?>
	<div id="j-main-container">
<?php
}?>
		<div id="filter-bar" class="btn-toolbar">
			<div class="filter-search btn-group pull-left">
				<label for="filter_search" class="element-invisible">
					<?php echo Text::_('JSEARCH_FILTER'); ?>
				</label>
				<input type="text" name="filter_search" id="filter_search"
					placeholder="<?php echo Text::_('COM_TJVENDOR_SEARCH_BY_USERNAME'); ?>"
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
			<div class="btn-group pull-right hidden-phone">
				<label for="limit" class="element-invisible">
					<?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?>
				</label>
				<?php echo $this->pagination->getLimitBox(); ?>
			</div>
		</div>
		<div class="clearfix"></div>
		<?php
		if(empty($this->items))
		{?>
			<div class="clearfix">&nbsp;</div>
				<div class="alert alert-no-items">
					<?php echo Text::_('COM_TJVENDOR_NO_MATCHING_RESULTS'); ?>
				</div>
		<?php
		}
		else
		{
			?>
		<table class="table table-striped" id="vendorList">
			<thead>
				<tr>
					<?php 
					if (isset($this->items[0]->ordering))
					{
						?>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'v.`ordering`', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
						</th>
					<?php 
					}
					?>
					<th width="1%" class="hidden-phone">
						<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)"/>
					</th>

					<?php if (isset($this->items[0]->state))
					{
					?>
						<th width="1%" >
							<?php echo Text::_('JSTATUS');?>
						</th>
					<?php 
					}
					?>
					<th width="5%">
						<?php echo HTMLHelper::_('grid.sort', 'COM_TJVENDORS_VENDORS_VENDOR_TITLE', 'v.`vendor_title`', $listDirn, $listOrder); ?>
					</th>
					<?php
					if ($this->vendorApproval)
					{
					?>
						<th width="2%">
							<?php echo Text::_('COM_TJVENDORS_VENDORS_VENDOR_APPROVE'); ?>
						</th>
					<?php 
					}?>
					<th width="5%">
						<?php echo Text::_('COM_TJVENDORS_VENDORS_ACTION_MENU'); ?>
					</th>
					<th width="5%" >
						<?php echo HTMLHelper::_('grid.sort', 'COM_TJVENDORS_VENDORS_ID', 'v.`vendor_id`', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php
				$options[] = array("type" => Text::_('COM_TJVENDORS_VENDORS_VENDOR_APPROVE'), "value" => "1");
				$options[] = array("type" => Text::_('COM_TJVENDORS_VENDORS_VENDOR_UNAPPROVED'), "value" => "0");

				foreach ($this->items as $i => $item)
				{
					$ordering   = ($listOrder == 'a.ordering');
					$canCreate  = $user->authorise('core.create', 'com_tjvendors');
					$canEdit    = $user->authorise('core.edit', 'com_tjvendors');
					$canCheckin = $user->authorise('core.manage', 'com_tjvendors');
					$canChange  = $user->authorise('core.edit.state', 'com_tjvendors');
					?>
					<tr class="row<?php echo $i % 2; ?>">
					<?php
						if (isset($this->items[0]->ordering))
						{
						?>
							<td class="order nowrap center hidden-phone">
								<?php
								if ($canChange)
								{
									$disableClassName = '';
									$disabledLabel    = '';

									if (!$saveOrder)
									{
										$disabledLabel    = Text::_('JORDERINGDISABLED');
										$disableClassName = 'inactive tip-top';
									}
								?>
									<span class="sortable-handler hasTooltip <?php echo $disableClassName ?>" title="<?php echo $disabledLabel ?>">
										<i class="icon-menu"></i>
									</span>
									<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order "/>
								<?php
								}
								else
								{
								?>
									<span class="sortable-handler inactive">
										<i class="icon-menu"></i>
									</span>
								<?php
								}?>
							</td>
						<?php
						}?>

							<td class="hidden-phone">
								<?php echo HTMLHelper::_('grid.id', $i, $item->vendor_id); ?>
							</td>
							<?php 
							if (isset($this->items[0]->state))
							{
							?>
								<?php $class = ($canChange) ? 'active' : 'disabled'; ?>
								<td >
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'vendors.', $canChange, 'cb'); ?>
								</td>
							<?php 
							} ?>
							<td>
								<a href="<?php echo Route::_(
								'index.php?option=com_tjvendors&view=vendor&layout=edit&client=' .
								$this->input->get('client', '', 'STRING') . '&vendor_id=' . (int) $item->vendor_id
								);?>">
									<?php echo $this->escape($item->vendor_title); ?>
								</a>
							</td>
							<?php
							if ($this->vendorApproval)
							{
							?>
								<td>
									<?php
									echo JHTML::_(
									'select.genericlist', $options, "vendorApprove", 'class="input-medium" size="1" onChange="tjVAdmin.vendors.vendorApprove(' .
									$item->vendor_id . ',this);"', 'value', 'type', $item->approved
									);
									?>
								</td>
							<?php 
							}?>
							<td>

								<a href="<?php echo Route::_('index.php?option=com_tjvendors&view=vendorfees&vendor_id=' . (int) $item->vendor_id) .
								'&client=' . $this->input->get('client', '', 'STRING'); ?>">
									<?php echo Text::_('COM_TJVENDORS_VENDORS_FEE'); ?>
								</a> |
								<a href="<?php echo Route::_('index.php?option=com_tjvendors&view=payouts&vendor_id=' . (int) $item->vendor_id) .
								'&client=' . $this->input->get('client', '', 'STRING'); ?>">
									<?php echo Text::_('COM_TJVENDORS_VENDORS_PAYOUTS'); ?>
								</a> |
								<a href="<?php echo Route::_('index.php?option=com_tjvendors&view=reports&vendor_id=' . (int) $item->vendor_id) .
								'&client=' . $this->input->get('client', '', 'STRING'); ?>">
									<?php echo Text::_('COM_TJVENDORS_VENDORS_REPORTS'); ?>
								</a>
							</td>
							<td >
								<?php echo $item->vendor_id; ?>
							</td>
						</tr>
				<?php
				}?>
			</tbody>
		</table>
		<?php
		}?>
			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="boxchecked" value="0"/>
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
			<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
