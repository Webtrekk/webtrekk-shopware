{block name="frontend_index_header_javascript_modernizr_lib" append}
	<script type="text/javascript">
		var webtrekkConfig = {$blWebtrekkConfig|json_encode nofilter};
	</script>
	<script type="text/javascript" src="{$blPathToWebtrekkJsFile}"></script>
	<script type="text/javascript">
		window._ti = {$blWebtrekkDatalayerAsJson};
	</script>
{/block}