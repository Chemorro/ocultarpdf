<div class="panel">
    <h3><i class="icon icon-cogs"></i> {l s='Configuration' mod='ocultarpdf'}</h3>
    <p>
        {l s='Select the customer groups for whom product attachment download links will be hidden.' mod='ocultarpdf'}
    </p>

    <form action="{$request_uri|escape:'html':'UTF-8'}" method="post" id="module_form" class="defaultForm form-horizontal">
        <div class="panel">
            <div class="panel-heading">
                {l s='Blocked Customer Groups' mod='ocultarpdf'}
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    <span class="label-tooltip" data-toggle="tooltip" title="{l s='Select the customer groups that will NOT be able to see product attachments. Hold CTRL (or Command on Mac) to select multiple groups.' mod='ocultarpdf'}">
                        {l s='Select Groups to Hide Attachments:' mod='ocultarpdf'}
                    </span>
                </label>
                <div class="col-lg-9">
                    <select name="OCULTARPDF_BLOCKED_GROUPS[]" multiple="multiple" class="form-control">
                        {foreach from=$groups item=group}
                            <option value="{$group.id_group|intval}" {if $group.id_group|in_array:$current_group_selection}selected="selected"{/if}>
                                {$group.name|escape:'html':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                    <p class="help-block">{l s='Select one or more customer groups. Customers belonging to these groups will not see product attachments.' mod='ocultarpdf'}</p>
                </div>
            </div>

            <div class="panel-footer">
                <button type="submit" value="1" id="module_form_submit_btn" name="submit{$module_name}" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Save' mod='ocultarpdf'}
                </button>
            </div>
        </div>
    </form>
</div>
