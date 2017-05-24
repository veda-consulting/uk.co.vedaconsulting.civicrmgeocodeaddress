{* HEADER *}

<!-- <div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div> -->

<div class="crm-block crm-form-block crm-export-form-block">

{* FIELD EXAMPLE: OPTION 1 (AUTOMATIC LAYOUT) *}

{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}

{* FIELD EXAMPLE: OPTION 2 (MANUAL LAYOUT)

  <!-- <div>
    <span>{$form.favorite_color.label}</span>
    <span>{$form.favorite_color.html}</span>
  </div> -->

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

</div>

{literal}
<script>
cj( document ).ready(function() {
  cj('#server').parent().append('<br />Without trailing slash. Example: http://pce.afd.co.uk , http://civipostcode.com');
  //cj('#update_city').parent().append('<br />Tick if you want to update city from geocode result');
  //cj('#update_county').parent().append('<br />Tick if you want to update county from geocode result');
  hideAllFields();
  showFields();
  cj('#is_geocoding_enabled').change(function() {
    hideAllFields();
    showFields();
  });

});

function hideAllFields() {
  cj('#server').parent().parent().hide();
  cj('#api_key').parent().parent().hide();
  cj('#provider').parent().parent().hide();  
}

function showFields() {
 if (cj('#is_geocoding_enabled').prop('checked')) {
    cj('#server').parent().parent().show();
    cj('#api_key').parent().parent().show();
    cj('#server').val('http://civipostcode.com');
    cj('#provider').parent().parent().show();
    //cj('#update_city').parent().parent().show();
    //cj('#update_county').parent().parent().show();
  } else {
    cj('#server').parent().parent().hide();
    cj('#api_key').parent().parent().hide();
    cj('#server').val('');
    cj('#provider').parent().parent().hide();
    //cj('#update_city').parent().parent().hide();
    //cj('#update_county').parent().parent().hide();
  }
}
</script>
{/literal}


