{* HEADER *}

{* First Test
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

{if $action & 1024}
  {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
{/if}

<div>
  <span>hello, world {$action}</span>
</div>
*}
{* ================================================================================== *}

{* Callback snippet: On-behalf profile *}

{if $snippet and !empty($isOnBehalfCallback)}
    <div class="crm-public-form-item crm-section">
        {include file="CRM/Contribute/Form/Contribution/OnBehalfOf.tpl" context="front-end"}
    </div>
{else}
{literal}
    <script type="text/javascript">

        // Putting these functions directly in template so they are available for standalone forms
        function useAmountOther() {
            var priceset = {/literal}{if $contriPriceset}'{$contriPriceset}'
            {else}0{/if}{literal};

            for (i = 0; i < document.Main.elements.length; i++) {
                element = document.Main.elements[i];
                if (element.type == 'radio' && element.name == priceset) {
                    if (element.value == '0') {
                        element.click();
                    }
                    else {
                        element.checked = false;
                    }
                }
            }
        }

        function clearAmountOther() {
            var priceset = {/literal}{if $priceset}'#{$priceset}'
            {else}0{/if}{literal}
            if (priceset) {
                cj(priceset).val('');
                cj(priceset).blur();
            }
            if (document.Main.amount_other == null) return; // other_amt field not present; do nothing
            document.Main.amount_other.value = "";
        }

    </script>
{/literal}

    {if $action & 1024}
        {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
    {/if}

    {include file="CRM/common/TrackingFields.tpl"}
    <div class="crm-contribution-page-id-{$contributionPageID} crm-block crm-contribution-main-form-block">

        {if $contact_id}
            <div class="messages status no-popup crm-not-you-message">
                {ts 1=$display_name}Welcome %1{/ts}. (<a
                        href="{crmURL p='civicrm/contribute/transact' q="cid=0&reset=1&id=`$contributionPageID`"}"
                        title="{ts}Click here to do this for a different person.{/ts}">{ts 1=$display_name}Not %1, or want to do this for a different person{/ts}</a>?)
            </div>
        {/if}

        <div id="intro_text" class="crm-public-form-item crm-section intro_text-section">
            {$intro_text}
        </div>
        {include file="CRM/common/cidzero.tpl"}
        {if $islifetime or $ispricelifetime }
            <div class="help">{ts}You have a current Lifetime Membership which does not need to be renewed.{/ts}</div>
        {/if}

        {if !empty($useForMember)}
            <div class="crm-public-form-item crm-section">
                {include file="CRM/Contribute/Form/Contribution/MembershipBlock.tpl" context="makeContribution"}
            </div>
        {else}
            <div id="priceset-div">
                {include file="CRM/Price/Form/PriceSet.tpl" extends="Contribution"}
            </div>
        {/if}

        {if $form.is_recur}
            <div class="crm-public-form-item crm-section {$form.is_recur.name}-section">
                <div class="label">&nbsp;</div>
                <div class="content">
                    {$form.is_recur.html} {$form.is_recur.label} {ts}every{/ts}
                    {if $is_recur_interval}
                        {$form.frequency_interval.html}
                    {/if}
                    {if $one_frequency_unit}
                        {$frequency_unit}
                    {else}
                        {$form.frequency_unit.html}
                    {/if}
                    {if $is_recur_installments}
                        <span id="recur_installments_num">
        {ts}for{/ts} {$form.installments.html} {$form.installments.label}
        </span>
                    {/if}
                    <div id="recurHelp" class="description">
                        {ts}Your recurring contribution will be processed automatically.{/ts}
                        {if $is_recur_installments}
                            {ts}You can specify the number of installments, or you can leave the number of installments blank if you want to make an open-ended commitment. In either case, you can choose to cancel at any time.{/ts}
                        {/if}
                        {if $is_email_receipt}
                            {ts}You will receive an email receipt for each recurring contribution.{/ts}
                        {/if}
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        {/if}
        {if $pcpSupporterText}
            <div class="crm-public-form-item crm-section pcpSupporterText-section">
                <div class="label">&nbsp;</div>
                <div class="content">{$pcpSupporterText}</div>
                <div class="clear"></div>
            </div>
        {/if}
        {assign var=n value=email-$bltID}
        <div class="crm-public-form-item crm-section {$form.$n.name}-section">
            <div class="label">{$form.$n.label}</div>
            <div class="content">
                {$form.$n.html}
            </div>
            <div class="clear"></div>
        </div>

        <div class="crm-public-form-item crm-section">
            {include file="CRM/Contribute/Form/Contribution/OnBehalfOf.tpl"}
        </div>

        {* User account registration option. Displays if enabled for one of the profiles on this page. *}
        {*
        <div class="crm-public-form-item crm-section cms_user-section">
            {include file="CRM/common/CMSUser.tpl"}
        </div>
        <div class="crm-public-form-item crm-section premium_block-section">
            {include file="CRM/Contribute/Form/Contribution/PremiumBlock.tpl" context="makeContribution"}
        </div>

        <div class="crm-public-form-item crm-group custom_pre_profile-group">
            {include file="CRM/UF/Form/Block.tpl" fields=$customPre}
        </div>
        *}

        {if $form.payment_processor_id.label}
            <fieldset class="crm-public-form-item crm-group payment_options-group" style="display:none;">
                <legend>{ts}Payment Options{/ts}</legend>
                <div class="crm-public-form-item crm-section payment_processor-section">
                    <div class="label">{$form.payment_processor_id.label}</div>
                    <div id="payment-options-only" class="content">{$form.payment_processor_id.html}</div>
                    {literal}
                        <script>
                            cj('#payment-options-only').find("input[value=0]").each(function () {
                                cj(this).remove();
                                cj('label[for=' + cj(this).attr('id') + ']').remove();
                            });
                        </script>
                    {/literal}
                    <div class="clear"></div>
                </div>
            </fieldset>
        {/if}

        {if $is_pay_later}
            <fieldset class="crm-public-form-item crm-group pay_later-group">
                <legend>{ts}Payment Options{/ts}</legend>
                <div class="crm-public-form-item crm-section pay_later_receipt-section">
                    <div class="label">&nbsp;</div>
                    <div class="content">
                        [x] {$pay_later_text}
                    </div>
                    <div class="clear"></div>
                </div>
            </fieldset>
        {/if}

        <div id="billing-payment-block">
            {include file="CRM/Pendingcontribution/Form/BillingBlock.tpl" snippet=4}
        </div>

        {include file="CRM/common/paymentBlock.tpl"}

        <div class="crm-public-form-item crm-group custom_post_profile-group">
            {include file="CRM/UF/Form/Block.tpl" fields=$customPost}
        </div>

        {if $is_monetary and $form.bank_account_number}
            <div id="payment_notice">
                <fieldset class="crm-public-form-item crm-group payment_notice-group">
                    <legend>{ts}Agreement{/ts}</legend>
                    {ts}Your account data will be used to charge your bank account via direct debit. While submitting this form you agree to the charging of your bank account via direct debit.{/ts}
                </fieldset>
            </div>
        {/if}

        {if $isCaptcha}
            {include file='CRM/common/ReCAPTCHA.tpl'}
        {/if}
        <div id="crm-submit-buttons" class="crm-submit-buttons">
            {include file="CRM/common/formButtons.tpl" location="bottom"}
        </div>
        {if $footer_text}
            <div id="footer_text" class="crm-public-form-item crm-section contribution_footer_text-section">
                <p>{$footer_text}</p>
            </div>
        {/if}
    </div>
    <script type="text/javascript">
        cj('#is_for_organization').remove();
        cj('label[for=is_for_organization]').remove();
        {if $isHonor}
        pcpAnonymous();
        {/if}

        {literal}

        cj('input[name="soft_credit_type_id"]').on('change', function () {
            enableHonorType();
        });

        function enableHonorType() {
            var selectedValue = cj('input[name="soft_credit_type_id"]:checked');
            if (selectedValue.val() > 0) {
                cj('#honorType').show();
            }
            else {
                cj('#honorType').hide();
            }
        }

        cj('input[id="is_recur"]').on('change', function () {
            toggleRecur();
        });

        function toggleRecur() {
            var isRecur = cj('input[id="is_recur"]:checked');
            var allowAutoRenew = {/literal}'{$allowAutoRenewMembership}'{literal};
            if (allowAutoRenew && cj("#auto_renew")) {
                showHideAutoRenew(null);
            }
            if (isRecur.val() > 0) {
                cj('#recurHelp').show();
                cj('#amount_sum_label').text(ts('Regular amount'));
            }
            else {
                cj('#recurHelp').hide();
                cj('#amount_sum_label').text(ts('Total amount'));
            }
        }

        function pcpAnonymous() {
            // clear nickname field if anonymous is true
            if (document.getElementsByName("pcp_is_anonymous")[1].checked) {
                document.getElementById('pcp_roll_nickname').value = '';
            }
            if (!document.getElementsByName("pcp_display_in_roll")[0].checked) {
                cj('#nickID').hide();
                cj('#nameID').hide();
                cj('#personalNoteID').hide();
            }
            else {
                if (document.getElementsByName("pcp_is_anonymous")[0].checked) {
                    cj('#nameID').show();
                    cj('#nickID').show();
                    cj('#personalNoteID').show();
                }
                else {
                    cj('#nameID').show();
                    cj('#nickID').hide();
                    cj('#personalNoteID').hide();
                }
            }
        }

        CRM.$(function ($) {
            enableHonorType();
            toggleRecur();
            skipPaymentMethod();
        });

        CRM.$(function ($) {
            // highlight price sets
            function updatePriceSetHighlight() {
                $('#priceset .price-set-row span').removeClass('highlight');
                $('#priceset .price-set-row input:checked').parent().addClass('highlight');
            }

            $('#priceset input[type="radio"]').change(updatePriceSetHighlight);
            updatePriceSetHighlight();

            // Update pledge contribution amount when pledge checkboxes change
            $("input[name^='pledge_amount']").on('change', function () {
                var total = 0;
                $("input[name^='pledge_amount']:checked").each(function () {
                    total += Number($(this).attr('amount'));
                });
                $("input[name^='price_']").val(total.toFixed(2));
            });
        });
        {/literal}
    </script>
{/if}

{* jQuery validate *}
{* disabled because more work needs to be done to conditionally require credit card fields *}
{*include file="CRM/Form/validate.tpl"*}




{* ================================================================================== *}
{* FOOTER

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
*}