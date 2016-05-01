<h3>{{$header}}</h3>

<p class="descriptive-text">{{$desc}}</p>
<p class="descriptive-text">{{$nav}}</p>

<h4>Pages Imported</h4>
<ul id='webpage-element-list'>
{{foreach $pages as $page}}
        <li class='plugin'>
                <div class='desc'>
                    Page Link: {{$page.pagelink}}<br>
                    Title: {{$page.title}}<br>
                    Content File: {{$page.contentfile}}<br>
                    Type: {{$page.type}}<br>
                    Import: {{if $page.import_success}}SUCCESS{{else}}FAILED{{/if}}<br>
                </div>
        </li>
{{/foreach}}
</ul>

<h4>Layouts Imported</h4>
<ul id='webpage-element-list'>
{{foreach $layouts as $layout}}
        <li class='plugin'>
                <div class='desc'>
                    Name: {{$layout.name}}<br>
                    Description: {{$layout.description}}<br>
                    Content File: {{$layout.contentfile}}<br>
                    Import: {{if $layout.import_success}}SUCCESS{{else}}FAILED{{/if}}<br>
                </div>
        </li>
{{/foreach}}
</ul>

<h4>Blocks Imported</h4>
<ul id='webpage-element-list'>
{{foreach $blocks as $block}}
        <li class='plugin'>
                <div class='desc'>
                    Name: {{$block.name}}<br>
                    Title: {{$block.title}}<br>
                    Content File: {{$block.contentfile}}<br>
                    Type: {{$block.type}}<br>
                    Import: {{if $block.import_success}}SUCCESS{{else}}FAILED{{/if}}<br>
                </div>
        </li>
{{/foreach}}
</ul>