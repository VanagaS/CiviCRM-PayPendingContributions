{* HEADER *}
{* disabled this for now *}
{*if $contributions|@count < 0}
    {if $ppcf_error_status}
        {assign var="my-error-message" value=$ppcf_error_message}
        <div class="messages status no-popup crm-not-you-message">
            <table>
                <tr>
                    <td>{$my-error-message}</td>
                </tr>
            </table>
        </div>
    {/if}
    {include file="CRM/Pendingcontribution/Form/PendingContributionList.tpl" location="top"}
{else *}
    <div class="crm-block crm-content-block crm-contribution-view-form-block">

        <div class="action-link">
            <div class="crm-submit-buttons">
                {include file="CRM/common/formButtons.tpl" location="top"}
            </div>
        </div>
        {if $ppcf_error_status}
            <div class="messages status no-popup crm-not-you-message">
                <span>{$ppcf_error_message}</span>
            </div>
        {/if}

        <br/><br/>
        {foreach from=$contributions item="contrib" name="contribution_loop"}
            {assign var="contribution_id" value=$contrib->getContributionID()}
            <table class="crm-info-panel">
                <tr>
                    <td class="label">{ts}Contribution Source{/ts}</td>
                    <td class="bold">{$contrib->getContributionSource()}</td>
                </tr>
                <tr>
                    <td class="label">{ts}Financial Type{/ts}</td>
                    <td>{$contrib->getFinancialType()}{if $contrib->is_test} {ts}(test){/ts} {/if}</td>
                </tr>
                {if $lineItem}
                    <tr>
                        <td class="label">{ts}Contribution Amount{/ts}</td>
                        <td>{include file="CRM/Price/Page/LineItem.tpl" context="Contribution"}
                            {if !$contrib->isIsRecurring()}
                                <strong>{ts}Recurring Contribution{/ts}</strong>
                                <br/>
                                {ts}Installments{/ts}: {if $recur_installments}{$recur_installments}{else}{ts}(ongoing){/ts}{/if}, {ts}Interval{/ts}: {$recur_frequency_interval} {$recur_frequency_unit}(s)
                            {/if}
                        </td>
                    </tr>
                {else}
                    <tr>
                        <td class="label">{ts}Total Amount{/ts}</td>
                        <td><strong><a class="nowrap bold crm-expand-row" title="{ts}view payments{/ts}"
                                       href="{crmURL p='civicrm/pay' q="view=transaction&component=contribution&action=browse&cid=`$contact_id`&id=`$contribution_id`&selector=1"}">
                                    &nbsp; {$contrib->getAmount()|crmMoney:$contrib->getCurrency()}
                            </strong></a>&nbsp;<span
                                    style="font-style: italic">&nbsp;{ts}(click on the amount to see a list of any past payments){/ts}</span>&nbsp;
                            {if $contribution_recur_id}
                                <strong>{ts}Recurring Contribution{/ts}</strong>
                                <br/>
                                {ts}Installments{/ts}: {if $recur_installments}{$recur_installments}{else}{ts}(ongoing){/ts}{/if}, {ts}Interval{/ts}: {$recur_frequency_interval} {$recur_frequency_unit}(s)
                            {/if}
                        </td>
                    </tr>
                {/if}
                {*if $invoicing && $tax_amount}
                    <tr>
                        <td class="label">{ts}Total Tax Amount{/ts}</td>
                        <td>{$contrib->getAmount()|crmMoney:$contrib->getCurrency()}</td>
                    </tr>
                {/if}
                {if $non_deductible_amount}
                    <tr>
                        <td class="label">{ts}Non-deductible Amount{/ts}</td>
                        <td>{$non_deductible_amount|crmMoney:$currency}</td>
                    </tr>
                {/if}
                {if $fee_amount}
                    <tr>
                        <td class="label">{ts}Fee Amount{/ts}</td>
                        <td>{$fee_amount|crmMoney:$currency}</td>
                    </tr>
                {/if}
                {if $net_amount}
                    <tr>
                        <td class="label">{ts}Net Amount{/ts}</td>
                        <td>{$net_amount|crmMoney:$currency}</td>
                    </tr>
                {/if}
                {if $isDeferred AND $revenue_recognition_date}
                    <tr>
                        <td class="label">{ts}Revenue Recognition Date{/ts}</td>
                        <td>{$revenue_recognition_date|crmDate:"%B, %Y"}</td>
                    </tr>
                {/if*}
                <tr>
                    {assign var="receive_date" value=$contrib->getReceiveDate()}
                    <td class="label">{ts}Received{/ts}</td>
                    <td>{if $receive_date}{$receive_date|crmDate}{else}({ts}not available{/ts}){/if}</td>
                </tr>
                {*if $to_financial_account }
                    <tr>
                        <td class="label">{ts}Received Into{/ts}</td>
                        <td>{$to_financial_account}</td>
                    </tr>
                {/if*}
                <tr>
                    {assign var="contribution_status_id" value=$contrib->getContributionStatus()}
                    <td class="label">{ts}Contribution Status{/ts}</td>
                    <td {if $contribution_status_id eq 3} class="font-red bold"{/if}>{$contribution_status}
                        {if $contribution_status_id eq 2} {if $is_pay_later}: {ts}Pay Later{/ts} {else} : {ts}Incomplete Transaction{/ts} {/if}{/if}</td>
                </tr>
                {if $cancel_date}
                    <tr>
                        <td class="label">{ts}Cancelled / Refunded Date{/ts}</td>
                        <td>{$cancel_date|crmDate}</td>
                    </tr>
                    {if $cancel_reason}
                        <tr>
                            <td class="label">{ts}Cancellation / Refund Reason{/ts}</td>
                            <td>{$cancel_reason}</td>
                        </tr>
                    {/if}
                    {if $refund_trxn_id}
                        <tr>
                            <td class="label">{ts}Refund Transaction ID{/ts}</td>
                            <td>{$refund_trxn_id}</td>
                        </tr>
                    {/if}
                {/if}
                <tr>
                    <td class="label">{ts}Payment Method{/ts}</td>
                    <td>{$payment_instrument}{if $payment_processor_name} ({$payment_processor_name}){/if}</td>
                </tr>
                {if $payment_instrument eq 'Check'|ts}
                    <tr>
                        <td class="label">{ts}Check Number{/ts}</td>
                        <td>{$check_number}</td>
                    </tr>
                {/if}
                <tr>
                    <td class="label">{ts}Source{/ts}</td>
                    <td>{$source}</td>
                </tr>
                {if $campaign}
                    <tr>
                        <td class="label">{ts}Campaign{/ts}</td>
                        <td>{$campaign}</td>
                    </tr>
                {/if}

                {if $contribution_page_title}
                    <tr>
                        <td class="label">{ts}Online Contribution Page{/ts}</td>
                        <td>{$contribution_page_title}</td>
                    </tr>
                {/if}
                {if $receipt_date}
                    <tr>
                        <td class="label">{ts}Receipt Sent{/ts}</td>
                        <td>{$receipt_date|crmDate}</td>
                    </tr>
                {/if}
                {foreach from=$note item="rec"}
                    {if $rec }
                        <tr>
                            <td class="label">{ts}Note{/ts}</td>
                            <td>{$rec}</td>
                        </tr>
                    {/if}
                {/foreach}

                {if $trxn_id}
                    <tr>
                        <td class="label">{ts}Transaction ID{/ts}</td>
                        <td>{$trxn_id}</td>
                    </tr>
                {/if}

                {if $invoice_id}
                    <tr>
                        <td class="label">{ts}Invoice ID{/ts}</td>
                        <td>{$invoice_id}&nbsp;</td>
                    </tr>
                {/if}

                {if $thankyou_date}
                    <tr>
                        <td class="label">{ts}Thank-you Sent{/ts}</td>
                        <td>{$thankyou_date|crmDate}</td>
                    </tr>
                {/if}
                <tr>
                    <td class="label">{ts}Make Payment{/ts}</td>
                    <td>
                        <div class="crm-actions-ribbon crm-contribpage-tab-actions-ribbon">
                            <ul id="actions">
                                <li>
                                    <div id="crm-contribpage-links-wrapper">
                                        {crmButton id="crm-contribpage-links-link-`$smarty.foreach.contribution_loop.index`" href="#" icon="bars"}{ts}Pay{/ts}{/crmButton}
                                        <div class="ac_results"
                                             id="crm-contribpage-links-list-{$smarty.foreach.contribution_loop.index}">
                                            <div class="crm-contribpage-links-list-inner">
                                                <ul>
                                                    <li><br/><a class="crm-contribution-test"
                                                                href="{crmURL p='civicrm/payment-processor-form' q="reset=1&action=preview&contribution=`$contribution_id`"}">{ts}Online Contribution (Test-drive){/ts}</a>
                                                    </li>
                                                    <li><a class="crm-contribution-live"
                                                           href="{crmURL p='civicrm/payment-processor-form' q="reset=1&contribution=`$contribution_id`" fe='true'}"
                                                           target="_blank">{ts}Online Contribution (Live){/ts}</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <!--div>
                                    {*help id="id-configure-contrib-pages"*}
                                </div-->
                            </ul>
                            <div class="clear"></div>
                            {assign var="links_list" value="#crm-contribpage-links-list-`$smarty.foreach.contribution_loop.index`"}
                            {assign var="links_link" value="#crm-contribpage-links-link-`$smarty.foreach.contribution_loop.index`"}
                            {literal}
                            <script>
                                cj('{/literal}{$links_list}{literal}').hide();
                                cj('body').click(function () {
                                    cj('{/literal}{$links_list}{literal}').hide();
                                });

                                cj('{/literal}{$links_link}{literal}').click(function (event) {
                                    cj('body').trigger('click');
                                    cj('{/literal}{$links_list}{literal}').toggle();
                                    event.stopPropagation();
                                    return false;
                                });
                            </script>
                            {/literal}
                        </div>
                    </td>
                </tr>
            </table>
            <div><br/></div>
        {/foreach}

        {if count($softContributions)} {* We show soft credit name with PCP section if contribution is linked to a PCP. *}
            <div class="crm-accordion-wrapper crm-soft-credit-pane">
                <div class="crm-accordion-header">
                    {ts}Soft Credit{/ts}
                </div>
                <div class="crm-accordion-body">
                    <table class="crm-info-panel crm-soft-credit-listing">
                        {foreach from=$softContributions item="softCont"}
                            <tr>
                                <td>
                                    <a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=`$softCont.contact_id`"}"
                                       title="{ts}View contact record{/ts}">{$softCont.contact_name}
                                    </a>
                                </td>
                                <td>{$softCont.amount|crmMoney:$currency}
                                    {if $softCont.soft_credit_type_label}
                                        ({$softCont.soft_credit_type_label})
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    </table>
                </div>
            </div>
        {/if}

        {if $premium}
            <div class="crm-accordion-wrapper ">
                <div class="crm-accordion-header">
                    {ts}Premium Information{/ts}
                </div>
                <div class="crm-accordion-body">
                    <table class="crm-info-panel">
                        <td class="label">{ts}Premium{/ts}</td>
                        <td>{$premium}</td>
                        <td class="label">{ts}Option{/ts}</td>
                        <td>{$option}</td>
                        <td class="label">{ts}Fulfilled{/ts}</td>
                        <td>{$fulfilled|truncate:10:''|crmDate}</td>
                    </table>
                </div>
            </div>
        {/if}

        {if $pcp_id}
            <div id='PCPView' class="crm-accordion-wrapper ">
                <div class="crm-accordion-header">
                    {ts}Personal Campaign Page Contribution Information{/ts}
                </div>
                <div class="crm-accordion-body">
                    <table class="crm-info-panel">
                        <tr>
                            <td class="label">{ts}Personal Campaign Page{/ts}</td>
                            <td><a href="{crmURL p="civicrm/pcp/info" q="reset=1&id=`$pcp_id`"}">{$pcp_title}</a><br/>
                                <span class="description">{ts}Contribution was made through this personal campaign page.{/ts}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">{ts}Soft Credit To{/ts}</td>
                            <td><a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=`$pcp_soft_credit_to_id`"}"
                                   id="view_contact"
                                   title="{ts}View contact record{/ts}">{$pcp_soft_credit_to_name}</a></td>
                        </tr>
                        <tr>
                            <td class="label">{ts}In Public Honor Roll?{/ts}</td>
                            <td>{if $pcp_display_in_roll}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
                        </tr>
                        {if $pcp_roll_nickname}
                            <tr>
                                <td class="label">{ts}Honor Roll Name{/ts}</td>
                                <td>{$pcp_roll_nickname}</td>
                            </tr>
                        {/if}
                        {if $pcp_personal_note}
                            <tr>
                                <td class="label">{ts}Personal Note{/ts}</td>
                                <td>{$pcp_personal_note}</td>
                            </tr>
                        {/if}
                    </table>
                </div>
            </div>
        {/if}

        {include file="CRM/Custom/Page/CustomDataView.tpl"}

        {if $billing_address}
            <fieldset>
                <legend>{ts}Billing Address{/ts}</legend>
                <div class="form-item">
                    {$billing_address|nl2br}
                </div>
            </fieldset>
        {/if}
    </div>
    {crmScript file='js/crm.expandRow.js'}
{*/if*}