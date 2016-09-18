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
<div class="crm-block crm-content-block">
    <h1>List of pending contributions for {$display_name|capitalize}</h1>
</div>
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
                                   href="#">
                                &nbsp; {$contrib->getAmount()|crmMoney:$contrib->getCurrency()}
                        </strong></a>
                        {if $contribution_recur_id}
                            <strong>{ts}Recurring Contribution{/ts}</strong>
                            <br/>
                            {ts}Installments{/ts}: {if $recur_installments}{$recur_installments}{else}{ts}(ongoing){/ts}{/if}, {ts}Interval{/ts}: {$recur_frequency_interval} {$recur_frequency_unit}(s)
                        {/if}
                    </td>
                </tr>
            {/if}
            <tr>
                {assign var="receive_date" value=$contrib->getReceiveDate()}
                <td class="label">{ts}Received{/ts}</td>
                <td>{if $receive_date}{$receive_date|crmDate}{else}({ts}not available{/ts}){/if}</td>
            </tr>
            <tr>
                {assign var="contribution_status" value=$contrib->getContributionStatus()}
                {assign var="contribution_status_id" value=$contrib->getContributionStatusID()}
                <td class="label">{ts}Contribution Status{/ts}</td>
                <td {if $contribution_status_id eq 3} class="font-red bold"{/if}>
                    {$contribution_status}
                    {if $contribution_status_id eq 2} {if $is_pay_later}: {ts}Pay Later{/ts} {else} : {ts}Incomplete Transaction{/ts} {/if}{/if}
                </td>
            </tr>
            <tr>
                {assign var="payment_instrument" value=$contrib->getPaymentInstrument()}
                <td class="label">{ts}Payment Method{/ts}</td>
                <td>{$payment_instrument}{if $payment_processor_name} ({$payment_processor_name}){/if}</td>
            </tr>
            {if $payment_instrument eq 'Check'|ts}
                <tr>
                    {assign var="check_number" value=$check_number|default:'None registered'}
                    <td class="label">{ts}Check Number{/ts}</td>
                    <td>{$check_number}</td>
                </tr>
            {/if}
            {if $source}
                <tr>
                    <td class="label">{ts}Source{/ts}</td>
                    <td>{$source}</td>
                </tr>
            {/if}
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
                                                            href="{crmURL p='civicrm/pending/payment-processor' q="reset=1&action=preview&contribution=`$contribution_id`"}">{ts}Online Contribution (Test-drive){/ts}</a>
                                                </li>
                                                <li><a class="crm-contribution-live"
                                                       href="{crmURL p='civicrm/pending/payment-processor' q="reset=1&contribution=`$contribution_id`" fe='true'}"
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
{*crmScript file='js/crm.expandRow.js'*}
{*/if*}