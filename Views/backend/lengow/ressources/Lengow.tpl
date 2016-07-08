{block name="backend/base/header/css" append}
    <style type="text/css">
		.lengow-enabled .x-tree-icon-parent  {
			width: 10px;
			height: 10px;
			padding-top: 10px;
			z-index:999;
			background: green !important; 
			-moz-border-radius: 5px; 
			-webkit-border-radius: 5px; 
			border-radius: 5px;
		}

		.lengow-disabled .x-tree-icon-parent  {
			width: 10px;
			height: 10px;
			padding-top: 10px;
			z-index:999;
			background: red !important; 
			-moz-border-radius: 5px; 
			-webkit-border-radius: 5px; 
			border-radius: 5px;
		}

        .lengow--icon {
            background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3wQIDioREM1LpQAAAWJJREFUKM9j/P//PwMpgAVT6NGrDw9evGNlYWZjYTZWkyGgIbFr5f3n7+TEBM7dfvrp2w9Rfp5d3WnCfFxwBUzIquduOyXIw/nu87fWFM8r80rEBHgjnAxSe1bhtOHCzUd+tnrvv3yXFRVgYGBoSfL4+//f6oOXkNUwwj0998aneXe+9ar/z+1b9ffff0YGBmZmJkZGBjYWZiVJ4YUVEeg2/Pj7/9mnn4yM7OKCvKwszBBBZibGi3efifDz4PS0hpzYlrYkZBHjjAnIXCY0Df+wRQvj71+4bMCm+sc3xp/Ybfj/+c3r//8w9Pz8zsDAiEXDr+/f////z4zuRgYmRkbs8SDCycLGzNR18pmpBM9/Bobf//7bCzPtPXPj5duPumqyWOKBgYHBeMGlR59+Qhz16eefbZbsMXVzONjZ9s2uUpYVw6KBgYHhwsuvEP6ff//VeZhuP35ppKnAiOQqRlKTNwDOtI3FqlWCYAAAAABJRU5ErkJggg==');
        }
    </style>
{/block}