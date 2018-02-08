{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_body_inline"}
    {$smarty.block.parent}
    {if isset($lengowVariables)}
        <!-- Tag_Lengow -->
        <img src="https://trk.lgw.io/lead?account_id={$lengowVariables['account_id']|escape:'htmlall':'UTF-8'}&order_ref={$lengowVariables['order_ref']|escape:'htmlall':'UTF-8'}&amount={$lengowVariables['amount']|escape:'htmlall':'UTF-8'}&currency={$lengowVariables['currency']|escape:'htmlall':'UTF-8'}&payment_method={$lengowVariables['payment_method']|escape:'htmlall':'UTF-8'}&cart={$lengowVariables['cart']|escape:'htmlall':'UTF-8'}&cart_number={$lengowVariables['cart_number']|escape:'htmlall':'UTF-8'}&newbiz={$lengowVariables['newbiz']|escape:'htmlall':'UTF-8'}" alt="" style="width: 1px; height: 1px; border: none;" />
        <img src="https://trk.lgw.io/validation?account_id={$lengowVariables['account_id']|escape:'htmlall':'UTF-8'}&order_ref={$lengowVariables['order_ref']|escape:'htmlall':'UTF-8'}&payment_method={$lengowVariables['payment_method']|escape:'htmlall':'UTF-8'}&valid={$lengowVariables['valid']|escape:'htmlall':'UTF-8'}" alt="" style="width: 1px; height: 1px; border: none;" />
        <!-- /Tag_Lengow -->
    {/if}
{/block}