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
		background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAACXlBMVEUAAAAuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEuMkEtMUArLz4rLz4rLz4rLz4tMUAtMUBDR1R3eoSHipKPkZlvcnw4PEotMUAtMUBDR1SpqrC1t7t+gIm0trvO0NORk5s0OEYuMkEuMkErLz5tcHqXmqGSlJwyNURHS1icnqSbnaROUl4sMD8uMkEtMUBCRVOoqrB8f4i8vcK5ur+6u8DIys3Iyc2anKMyNkQuMkEsMD9PU1/m5+mFiJCpq7G2t721t7y0tbrf4OLLzNA0OEcuMkFPU1/o6Opsb3ksMD8vM0IvM0ItMUCfoajNztE0OEfo6OprbnkrLz4uMkEsMD+foajNztFPU1/o6OprbnkrLz4sMD+foajNztEsMD9PU1/o6epqbXgnLDsrLz4sMD8qLj2foafNztEsMEBGSVff3+K4ub6GiZGJi5NjZnF0d4DX2Nq4ur8xNUQuMkEvM0J4e4TQ0dTc3eDe3uGUl56ztLnFxspZXWgtMUAtMUA5PEs+QU8+Qk83O0k6Pkw2OkgtMUAtMUAtMUAtMUAtMUAtMUAtMUH///+4NrmbAAAAR3RSTlMAAAdIpeD6SAcZke3tkRiv/v6vGQaQ//+QBkjs7EmlpeDg+vn64OClpkjs7EkGkP//kQYZr/7+rxkYkO3tkBhIpeD5+eClB3fniK8AAAABYktHRMlqvWdcAAAAB3RJTUUH4AcLCjIdPFBXTQAAARJJREFUGNNjYGBgZGJmYWVjY2Vh52BkAHIZObm43T08vbx9eHg5GRkZGPn4BXz9/AMCg4JDBIWEGRlERMVCw8IjIqOiY2LjxCUkGaSk4xMSk5JTUtPSMzKzZGQZ5LKyc3Lz8gsKi4pLSsuy5BkUssorKquqa2rr6hsam7IUGZSyyptbWtvaOzq7unuaspQZVIACvX39EyZMnDQZLKCaVT5l6rTpWVkzZs4CCqgxqGfNnjN33vwFCxctXgIU0GDQ1Fq6bPmKlatWr1m7bv0GbR0GXT39jZs2b9m6bfuOnbsMDI0YGI1NTHfv2btv/4GDh8zMLYB+YbS0sj585Oix4ydsbO0Ywf7lsHdwdHJ2cWV3A3IBc6haztJmEyQAAAAldEVYdGRhdGU6Y3JlYXRlADIwMTYtMDctMTFUMTA6NTA6MjktMDQ6MDAJpq8GAAAAJXRFWHRkYXRlOm1vZGlmeQAyMDE2LTA3LTExVDEwOjUwOjI5LTA0OjAwePsXugAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAAASUVORK5CYII=');
	}
</style>
{/block}