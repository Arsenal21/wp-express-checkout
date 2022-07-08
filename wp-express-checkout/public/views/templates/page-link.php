<html>
    <head>

        <?php wp_head() ?>

        <style>
            #wp-express-checkout-button-cont .wpec-modal-open {
                display: none;
            }

            #wp-express-checkout-button-cont .wpec-modal-close {
                display: none;
            }

            .wpec-modal-overlay {
                pointer-events: none;
            }

            .wpec-modal-content {
                pointer-events: auto !important;
            }

            #wp-express-checkout-error-product-id {
                
                text-align: center;
                font-size: 18px;
                background: red;
                color: white;
                top: 40%;
                position: relative;
                width: 300px;
                left: calc(50% - 150px);
            }

        </style>

        <script type="text/javascript">
            window.addEventListener( "load", () => {
                
                const btnElement = document.getElementsByClassName("wpec-modal-open")[0];

                btnElement.click();
              
            } );
        </script>
    </head>

    <body>
        
        <?php if ( get_post( $_GET["product_id"] ) ): ?>
            
            
            <div id="wp-express-checkout-button-cont">

                <?php echo do_shortcode( sprintf(  "[wp_express_checkout product_id='%d']", $_GET["product_id"] ) ) ?>

            </div>
            

        <?php else: ?>
            <div id="wp-express-checkout-error-product-id">
                <p><strong>Product does not exist.</strong></p>
            </div>
        <?php endif; ?>
    </body>
    <?php wp_footer() ?>
</html>