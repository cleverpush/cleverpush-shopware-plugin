{extends file="parent:frontend/index/header.tpl"}

{block name="frontend_index_header_javascript_tracking"}
    {$smarty.block.parent}

    <script>
        var cleverPushConfig = {$cleverPushConfig};
    </script>
    <script src="https://static.cleverpush.com/sdk/cleverpush.js" async></script>
{/block}
