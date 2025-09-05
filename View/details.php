<article id="layout"></article>
<script>
    (function () {
        $(document).ready(function(){
            builder.Layout('user',"#layout",{url: '/api/users/fetch?id=<?= $this->Request->getParams('GET', 'id') ?>'});
        });
    })();
</script>
