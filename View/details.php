<article id="layout"></article>
<script>
    (function () {
        $(document).ready(function(){
            builder.Layout('user',"#layout",{endpoint: '/users/fetch?id=<?= $this->Request->getParams('GET', 'id') ?>'});
        });
    })();
</script>
