/* 
    Basado en mooSnow de Dimitar Christoff
    http://fragged.org/dev/moosnow-javascript-text-snow-flakes-class.php

    Creado y adaptado para wordpress por Jose Maria Sampedro [KeLDroX]
    http://www.rutarelativa.com
    ¡Feliz Navidad!
 */

(function($){
  $.getRandom = function(arr)
  {
    aleat = Math.floor(arr.length * Math.random());

    return arr[aleat]
  }
})(jQuery);

jQuery.bind = function(object, method){
  var args = Array.prototype.slice.call(arguments, 2);
  return function() {
    var args2 = [this].concat(args, $.makeArray( arguments ));
    return method.apply(object, args2);
  };
};

( function($)
{
   window.moosnow = mooSnow =
    {
        getOptions: function()
        {
            return {
                directions : [ "left", "right", "straight" ],
                container : $(document.body),
                inject    : "top", // inject stage inside, top, before or after the container
                stage     :
                {
                    styles :
                    {
                        background : "none",
                        width      : null,
                        height     : null,
						top 	   : 0,
                        display    : "block",
                        position   : "absolute",
                        overflow   : "hidden"
                    },
                    padding : 1 // horisontal stage padding
                },
                snows :
                {
                    amount: 40, // number of snowflakes	
                    speed: [1,2,3], //speed with wich individual snowflakes fall
                    symbol: ["*"], //an array of flake symbols, html can be used as well
                    color: ["#efefef", "#eee", "#eeeedd"], //flake colours
                    fontFamily: ['Impact', 'Times New Roman', 'Georgia'], //different flake shapes
                    fontSize: [20, 22], //font size in pixels
                    direction: "left", //left,right,straight
                    opacity: [3, 1], // opacity min and opacity max for flakes
                    sinkSpeed: 50 //how fast the snow is falling
                }
            };
        },

        initialize: function(options)
        {
            this.options = $.extend( this.getOptions(), options );
            this.container = this.options.container;

            if( !this.container )
                return;

            this.createStage();
            this.createSnow();
            this.timer = setInterval( $.bind(this, this.animateSnow ), this.options.snows.sinkSpeed );
        },

		init : function(){
			return this.initialize();
		},
	
		stop : function(){
			this.container.find(".snow-piece").remove();
			clearInterval(this.timer);
		},

        createStage: function()
        {
			this.stage = this.container;
            var size = { "x": this.container.outerWidth(), "y": this.container.outerHeight() };
            this.options.stage.styles.height = this.options.stage.styles.height || size.y;
            this.options.stage.styles.width  = this.options.stage.styles.width || size.x;

            /*this.container.append( '<div id="snow_stage"></div>' );

			
            this.stage = $( "#snow_stage" );
            this.stage.css( this.options.stage.styles );

            var size = { "x": this.container.outerWidth(), "y": this.container.outerHeight() };
            this.options.stage.styles.height = this.options.stage.styles.height || size.y;
            this.options.stage.styles.width  = this.options.stage.styles.width || size.x;

            this.stage.css(
            {
                height: this.options.stage.styles.height,
                width: this.options.stage.styles.width
            });
			**/
        },

        createSnow: function()
        {
            // creates all the snowflakes and assigns them size, colour, opacity and direction
            var stagePadding = this.options.stage.styles.width / 100 * this.options.stage.padding;
            var stepX = (this.options.stage.styles.width - stagePadding / 2) / this.options.snows.amount;
            var stepY = 0;
            var posX = stepX/2;
            var posY = 0;
            var variateX = [stepX/-3, stepX/3];
            var aux;


            this.snow = new Array();

            for( i = 0; i < this.options.snows.amount; i++ )
            {
                if( stepY >= 100 )
                    stepY = 0;

                posY = this.options.stage.styles.height / -100 * stepY;
                stepY += 25;

                this.stage.append( '<div id="snow_' + i + '" class="snow-piece"></div>' );
                aux = $( "#snow_" + i );
                aux.css( { 
                           "fontFamily" : $.getRandom( this.options.snows.fontFamily ),
                           "fontSize"   : $.getRandom( this.options.snows.fontSize ),
                           "color"      : $.getRandom( this.options.snows.color ),
                           "position"   : "absolute",
                           "top"        : posY,
                           "left"       : posX,
						   "z-index"	: 9999999,
                           "opacity"  : (this.options.snows.opacity.size ? $.getRandom( [this.options.snows.opacity[0]*10, this.options.snows.opacity[1]*10 ] ) / 10 : this.options.snows.opacity)
                         } );
                aux.html( $.getRandom(this.options.snows.symbol) );
                aux.data( "direction", $.getRandom( this.options.directions ) );

                this.snow.push( aux );

                posX += stepX + $.getRandom( variateX );
            }
        },

        animateSnow: function()
        {
            var floor = this.options.stage.styles.height;
            var stagePadding = this.options.stage.styles.width / 100 * this.options.stage.padding;

            if( ! this.snow.length )
            {
                clearTimeout( this.timer );
                return;
            }

            $.each(this.snow, $.bind( this, function(flake, i)
            {
                var top = parseInt( flake.css( "top" ) ) + $.getRandom( this.options.snows.speed );
                top = (top >= floor ? 0 : top);
                flake.css( "top", top );

                var moveDirection = flake.data( "direction" );

                if (moveDirection == 'left')
                {
                    var left = parseInt( flake.css('left') ) - $.getRandom( [1,2] );
                    left = (left < stagePadding /2) ? this.options.stage.styles.width - stagePadding/2 : left;
                }
                else if (moveDirection == 'right')
                {
                    var left = parseInt( flake.css('left') ) + 1;
                    left = (left > this.options.stage.styles.width - stagePadding/2) ? stagePadding/2 : left;
                }
                else
                    var left = parseInt( flake.css('left') );

                flake.css('left', left);
            } ) );
        }
    }
})(jQuery);
