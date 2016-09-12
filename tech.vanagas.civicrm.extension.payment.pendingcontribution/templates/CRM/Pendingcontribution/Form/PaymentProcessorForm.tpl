{if $action & 1024}
    {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
{/if}
{if !$ppcf_error_status}
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

    <div id="billing-payment-block">
        {include file="CRM/Financial/Form/Payment.tpl" snippet=4}
    </div>

    <div id="crm-submit-buttons" class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>

<script type="text/javascript">
    {literal}

    {/literal}
</script>
