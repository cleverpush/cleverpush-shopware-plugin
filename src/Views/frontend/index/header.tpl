{extends file="parent:frontend/index/header.tpl"}

{block name="frontend_index_header_javascript_tracking"}
    {$smarty.block.parent}

    <script src="hhttps://static.cleverpush.com/channel/loader/{$cleverPushChannelId}.js" async></script>
{/block}
