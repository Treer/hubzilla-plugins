<script>
    function checkedAll(isChecked) {
        var c = document.getElementsByTagName('input');

        for (var i = 0; i < c.length; i++){
            if (c[i].type == 'checkbox'){
                c[i].checked = isChecked;
            }
        }
    }
</script>
<h3>{{$header}}</h3>

<p class="descriptive-text">{{$desc}}</p>

<form action="hubsites" method="post" autocomplete="on" >
    <input type="hidden" name="action" value="{{$action}}">
    <div class="selectall pull-right"><a href="#" onclick="checkedAll(false); return false;">{{$deselect_all}}</a></div>
    <div class="selectall pull-right"><a href="#" onclick="checkedAll(true); return false;">{{$select_all}}</a>/</div>
    <input type="submit" name="submit" value="{{$submit}}" />
<h4>Scanned Pages</h4>
<div class="hubsites-table">
<table>
    <tr><td>Import?</td><td>Repo Page</td><td>Existing Page</td></tr>
{{foreach $pages as $page}}
        <tr>
            <td>
                <div class='squaredTwo'>
                <input type="checkbox" id="page_{{$page.pagelink}}" name="page[]" value="{{$page.pagelink}}">
                <label for="page_{{$page.pagelink}}"></label>
                </div>
            </td>
            <td>
                <div class='desc'>
                    Page Link: {{$page.pagelink}}<br>
                    Layout: {{$page.layout}}<br>
                    Title: {{$page.title}}<br>
                    Content File: {{$page.contentfile}}<br>
                    Type: {{$page.type}}<br>
                </div>
            </td>
            <td>
                <div class='desc'>
                    Name: {{$page.curpage.pagelink}}<br>
                    Layout: {{$page.curpage.layout}}<br>
                    Title: {{$page.curpage.title}}<br>
                    Last edit: {{$page.curpage.edited}}<br>
                    Type: {{$page.curpage.type}}<br>
                </div>
            </td>
        </tr>
{{/foreach}}
</table>
</div>

<h4>Scanned Layouts</h4>
<div class="hubsites-table">
<table>
    <tr><td>Import?</td><td>Repo Layout</td><td>Existing Layout</td></tr>
{{foreach $layouts as $layout}}
        <tr>
            <td>
                <div class='squaredTwo'>
                <input type="checkbox" id="layout_{{$layout.name}}" name="layout[]" value="{{$layout.name}}">
                <label for="layout_{{$layout.name}}"></label>
                </div>
            </td>
            <td>
                <div class='desc'>
                    Name: {{$layout.name}}<br>
                    Description: {{$layout.description}}<br>
                    Content File: {{$layout.contentfile}}<br>
                </div>
            </td>
            <td>
                <div class='desc'>
                    Name: {{$layout.curblock.name}}<br>
                    Title: {{$layout.curblock.description}}<br>
                    Last edit: {{$layout.curblock.edited}}<br>
                </div>
            </td>
        </tr>
{{/foreach}}
</table>
</div>

<h4>Scanned Blocks</h4>
<div class="hubsites-table">
<table>
    <tr><td>Import?</td><td>Repo Block</td><td>Existing Block</td></tr>
{{foreach $blocks as $block}}
        <tr>
            <td>
                <div class='squaredTwo'>
                <input type="checkbox" id="block_{{$block.name}}" name="block[]" value="{{$block.name}}">
                <label for="block_{{$block.name}}"></label>
                </div>
            </td>
            <td>
                <div class='desc'>
                    Name: {{$block.name}}<br>
                    Title: {{$block.title}}<br>
                    Content File: {{$block.contentfile}}<br>
                    Type: {{$block.type}}<br>
                </div>
            </td>
            <td>
                <div class='desc'>
                    Name: {{$block.curblock.name}}<br>
                    Title: {{$block.curblock.title}}<br>
                    Last edit: {{$block.curblock.edited}}<br>
                    Type: {{$block.curblock.type}}<br>
                </div>
            </td>
        </tr>
{{/foreach}}
</table>
</div>
</form>