{block name="frontend_index_header_javascript_jquery_lib" append}
{literal}
	<script type="text/javascript">
		$( document ).ready(function() {
			$('#buybox--button').on('click', function(){
				window._ti.productStatus='add';
			})
		});
	</script>

	<script type="text/javascript">
		$( document ).ready(function() {
			$('#sQuantity').on('change', function(){
				window._ti.productQuantity=document.getElementById('sQuantity').value;
			})
		});
	</script>
{/literal}
{/block}