
  {if $action & 1024}
    {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
  {/if}

  <div class="crm-contribution-page-id-{$contributionPageID} crm-block crm-contribution-main-form-block">
    {if $contact_id}
      <div class="messages status no-popup crm-not-you-message">
        {ts 1=$display_name}Welcome %1{/ts}. (<a href="{crmURL p='civicrm/contribute/transact' q="cid=0&reset=1&id=`$contributionPageID`"}" title="{ts}Click here to do this for a different person.{/ts}">{ts 1=$display_name}Not %1, or want to do this for a different person{/ts}</a>?)
      </div>
    {/if}

    <div id="pricesetTotal" class="crm-section section-pricesetTotal">
      <div class="label">
        <label for="contribution_amount"> Total Amount
          <span id="amount_sum_label_2" class="crm-marker" title="This field is required.">*</span>
        </label>
      </div>
      <div class="content other_amount-content">
        <input price="[23,&quot;1||&quot;]" size="4" name="contribution_amount"
               id="contribution_amount" class="four crm-form-text required" type="text" value="{$contribution_amount}" readonly>
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
