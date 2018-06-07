<!DOCTYPE html>
<html>
    <head>
        <title>Simple pdf.js page viewer</title>

        <link rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/main.css"/>
        <link rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/pdf.js/viewer.css"/>

		<script type="text/javascript">
			var kDefaultURL = "{$file}";
		</script>
		<script type="text/javascript" src="{$smarty.const.RESOURCES_DOMAIN}/js/pdf.js/build/pdf.js"></script>
        <script type="text/javascript" src="{$smarty.const.RESOURCES_DOMAIN}/js/pdf.js/viewer.js"></script>
		<script type="text/javascript">
			PDFJS.workerSrc = "../res/js/pdf.js/build/pdf.js";
		</script>
	</head>
	<body>

   <div id="controls">
      <button class="btn" id="previous" onclick="PDFView.page--;" oncontextmenu="return false;">
        <span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/pdf.js/go-up.svg" style="vertical-align:top; height:16px" height="16"/></span></span>
      </button>

      <button class="btn" id="next" onclick="PDFView.page++;" oncontextmenu="return false;" style="margin-left:-12px">
        <span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/pdf.js/go-down.svg" style="vertical-align:top; height:16px" height="16"/></span></span>
      </button>

      <div class="separator"></div>

      <input type="number" style="border: 1px solid #D1D1D1; padding:4px; vertical-align:middle; font-size:12px; height:1em; width:25px;" id="pageNumber" onchange="PDFView.page = this.value;" value="1" size="2" min="1" />

	  <span style="font-size: 12px; border: 1px solid #D1D1D1; border-left: 0px; background-color: #FFFFFF; vertical-align:middle; padding: 2px; height:1em; margin-left: -10px">
		<span>/</span>
		<span id="numPages">--</span>
	  </span>

      <div class="separator"></div>

      <button class="btn" id="zoomOut" title="Zoom Out" onclick="PDFView.zoomOut();" oncontextmenu="return false;">
        <span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/pdf.js/zoom-out.svg" style="vertical-align:top; height:16px" height="16"/></span></span>
      </button>
      <button class="btn" id="zoomIn" title="Zoom In" onclick="PDFView.zoomIn();" oncontextmenu="return false;" style="margin-left:-12px">
        <span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/pdf.js/zoom-in.svg" style="vertical-align:top; height:16px" height="16"/></span></span>
      </button>

      <select id="scaleSelect" onchange="PDFView.parseScale(this.value);" oncontextmenu="return false;">
        <option id="customScaleOption" value="custom"></option>
        <option value="0.5">50%</option>
        <option value="0.75">75%</option>
        <option value="1">100%</option>
        <option value="1.1">110%</option>
        <option value="1.25">125%</option>
        <option value="1.5">150%</option>
        <option value="2">200%</option>
        <option id="pageWidthOption" value="page-width">Page Width</option>
        <option id="pageFitOption" value="page-fit">Page Fit</option>
        <option id="pageAutoOption" value="auto" selected="selected">Auto</option>
      </select>

      <div class="separator"></div>

      <button  class="btn" id="print" onclick="window.print();" oncontextmenu="return false;">
        <span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/pdf.js/document-print.svg" style="vertical-align:top; height:16px" height="16"/></span></span>
      </button>


      <span id="info">--</span>
    </div>
    <div id="errorWrapper" hidden='true'>
      <div id="errorMessageLeft">
        <span id="errorMessage"></span>
        <button id="errorShowMore" onclick="" oncontextmenu="return false;">
          More Information
        </button>
        <button id="errorShowLess" onclick="" oncontextmenu="return false;" hidden='true'>
          Less Information
        </button>
      </div>
      <div id="errorMessageRight">
        <button id="errorClose" oncontextmenu="return false;">
          Close
        </button>
      </div>
      <div class="clearBoth"></div>
      <textarea id="errorMoreInfo" hidden='true' readonly="readonly"></textarea>
    </div>

	{*
    <div id="sidebar">
      <div id="sidebarBox">
        <div id="pinIcon" onClick="PDFView.pinSidebar()"></div>
        <div id="sidebarScrollView">
          <div id="sidebarView"></div>
        </div>
        <div id="outlineScrollView" hidden='true'>
          <div id="outlineView"></div>
        </div>
        <div id="sidebarControls">
          <button id="thumbsSwitch" title="Show Thumbnails" onclick="PDFView.switchSidebarView('thumbs')" data-selected>
            <img src="{$smarty.const.RESOURCES_DOMAIN}/img/pdf.js/nav-thumbs.svg" align="top" height="16" alt="Thumbs" />
          </button>
          <button id="outlineSwitch" title="Show Document Outline" onclick="PDFView.switchSidebarView('outline')" disabled>
            <img src="{$smarty.const.RESOURCES_DOMAIN}/img/pdf.js/nav-outline.svg" align="top" height="16" alt="Document Outline" />
          </button>
        </div>
      </div>
    </div>
	*}

    <div id="loading">Loading... 0%</div>
    <div id="viewer"></div>
	</body>
</html>
