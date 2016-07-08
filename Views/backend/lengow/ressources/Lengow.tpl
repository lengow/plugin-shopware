{block name="backend/base/header/css" append}
<style type="text/css">
	.lengow-enabled .x-tree-icon-parent  {
		/*background-image:url("icons/enabled.png") !important;*/
		background-repeat: no-repeat;
	}

	.lengow-disabled .x-tree-icon-parent  {
		/*background-image:url("icons/disabled.png") !important;*/
		background-repeat: no-repeat;
	}

	.lengow_label_not_synchronized {
		border-color: #00aeef;
		color: #00aeef;
	}

	.lengow_check_shop {
		margin-left: 5px;
		margin-right: 5px;
		display: inline-block;
		width: 5px;
		height: 5px;
		padding:5px;
		border-radius: 10px;
	}

	.lengow_check_shop_sync {
		background-color: #45bf7b;
	}

	.lengow_check_shop_no_sync {
		background-color: #e55a4d;
	}

	.lengow_shop_status_label span{
		color:#555;
		font-weight:normal;
		font: 400 12px/1.42857 "Open Sans",Helvetica,Arial,sans-serif;
	}

	.lengow_shop_status_label a {
		text-decoration: underline;
	}

	.lengow_shop_status_label a:hover {
		text-decoration: none;
	}

	.lengow_shop_status_label a span:hover{
		color:#00aeef;
	}

	.lengow_export_feed {
	    display:inline-block;
	    margin-left: 10px;
	    height:10px;
	    text-decoration:none;
	}

	.lengow_export_feed:before{
	    content: '';
	    background:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAA+klEQVR4XsVSQWqFMBTME1FQMEVwIQhFcK14A29ijtacpP8G4l6wBcGFYP1LF2pf+tsgaYR01YHH6GRmCE+BaFCW5TPSm6o3TfPLbwvSGN/zPCcmsK8M27b9Y0GWZRQLtHrXdfezJpeSpulB/oi+70HeYN93dhzHi2kYANgXn8UkSWpRYhIehoHLgjPiOJYlV+FxHLl815miKKqRdCVsmiauLFGPMAzVEjbPM9eagyCgYlSdUlrjvAq+zHieR33f/xCDzwUxgPD9ZOzTX/eEc3Ndt1rXtb0K43mBn/z27V9AiI7jFLj5h2iOBQAquUTLsgok05IFp8KbtJ/QFlq51IfJOwAAAABJRU5ErkJggg==');
	    background-size:cover;
        position:absolute;
	    width:12px;
	    height:12px;
	}

	.lengow_export_feed :hover{
		background-color:blue;
	    background-size:cover;
	}

	.lengow--icon {
		background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3wQIDioREM1LpQAAAWJJREFUKM9j/P//PwMpgAVT6NGrDw9evGNlYWZjYTZWkyGgIbFr5f3n7+TEBM7dfvrp2w9Rfp5d3WnCfFxwBUzIquduOyXIw/nu87fWFM8r80rEBHgjnAxSe1bhtOHCzUd+tnrvv3yXFRVgYGBoSfL4+//f6oOXkNUwwj0998aneXe+9ar/z+1b9ffff0YGBmZmJkZGBjYWZiVJ4YUVEeg2/Pj7/9mnn4yM7OKCvKwszBBBZibGi3efifDz4PS0hpzYlrYkZBHjjAnIXCY0Df+wRQvj71+4bMCm+sc3xp/Ybfj/+c3r//8w9Pz8zsDAiEXDr+/f////z4zuRgYmRkbs8SDCycLGzNR18pmpBM9/Bobf//7bCzPtPXPj5duPumqyWOKBgYHBeMGlR59+Qhz16eefbZbsMXVzONjZ9s2uUpYVw6KBgYHhwsuvEP6ff//VeZhuP35ppKnAiOQqRlKTNwDOtI3FqlWCYAAAAABJRU5ErkJggg==');
	}
</style>
{/block}