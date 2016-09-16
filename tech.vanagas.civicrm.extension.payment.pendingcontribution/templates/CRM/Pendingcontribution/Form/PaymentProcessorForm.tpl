{if $action & 1024}
    {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
{/if}
{if $ppcf_error_status}
    <div class="messages status no-popup">
        <span>{$ppcf_error_message}</span>
    </div>
{/if}
<div class="crm-contribution-page-id-{$contributionPageID} crm-block crm-contribution-main-form-block">

    <fieldset class="billing_mode-group credit_card_info-group">
        <legend>Payment Information</legend>
    </fieldset>
    <div id="pricesetTotal" class="crm-section section-pricesetTotal">
        <div class="label">
            <span> Total Amount</span>
        </div>
        <div class="content other_amount-content">
            <span id="total_amount">{$contribution_amount|crmMoney:$currency}</span>
        </div>
        <div class="clear"></div>
    </div>
    {if $form.payment_processor_id.label}
        {* PP selection only works with JS enabled, so we hide it initially *}
        <fieldset class="crm-public-form-item crm-group payment_options-group" style="display:none;">
            <legend>{ts}Payment Options{/ts}</legend>
            <div class="crm-public-form-item crm-section payment_processor-section">
                <div class="label">{$form.payment_processor_id.label}</div>
                <div class="content">{$form.payment_processor_id.html}</div>
                <div class="clear"></div>
            </div>
        </fieldset>
    {/if}
    <div id="billing-payment-block">
        {include file="CRM/Core/BillingBlock.tpl" snippet=4}
    </div>

    <div id="crm-submit-buttons" class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>

<script type="text/javascript">
    {literal}

    {/literal}
</script>