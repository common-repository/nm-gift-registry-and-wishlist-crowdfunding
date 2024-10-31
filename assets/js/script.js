jQuery(function($) {

    $(document.body)
        .on('click', '.nmgrcf-post-action', post_action)
        .on('nmgr_post_action_response', post_action_response)
        .on('submit', '.nmgrcf-form', form_submit)
        .on('click', '[data-nmgrcf_disabled_notice]', show_disabled_notice);


    function post_action_response(e, args) {
        if (args.response.replace_templates) {
            if (args.response.replace_templates.hasOwnProperty('#nmgr-free_contributions')) {
                nmgr.free_contributions.reloaded(args.response.wishlist || null);
            }
            if (args.response.replace_templates.hasOwnProperty('#nmgr-crowdfunds')) {
                nmgr.crowdfunds.reloaded(args.response.wishlist || null);
            }
        }
    }

    function post_action(e) {
        e.preventDefault();

        var toBlock = [this];

        if (this.dataset.nmgr_block) {
            try {
                var dataBlock = JSON.parse(this.dataset.nmgr_block);
                var dataBlockArray = Array.isArray(dataBlock) ? dataBlock : [dataBlock];
                toBlock = toBlock.concat(dataBlockArray);
            } catch (e) {
                console.warn(e);
            }
        }

        var args = {
            btn: this,
            block: toBlock,
            postdata: this.dataset
        };

        if (nmgr.hasOwnProperty('post_action')) {
            nmgr.post_action(args);
        }
    }

    function form_submit(e) {
        e.preventDefault();

        if ('function' === typeof(Nmeri_getFormdata)) {
            var postdata = Object.assign(Nmeri_getFormdata(this), {
                nmgr_post_action: this.dataset.nmgr_post_action,
                nmgr_global: JSON.stringify(nmgr_global_params.global)
            });

            var args = {
                block: this,
                btn: e.originalEvent.submitter,
                postdata: postdata
            };

            nmgr.post_action(args);
        }
    }

    function show_disabled_notice(e) {
        e.preventDefault();
        var notice = this.dataset.nmgrcf_disabled_notice;
        if (notice) {
            alert(notice);
        }
    }




    nmgr.crowdfunds = {



        init: function() {



            $(document.body)



                .on('change', '.atw-cf-enable', this.toggle_options_container)



                .on('change', 'select.list-of-wishlists', this.update_crowdfund_status_for_wishlist);



        },







        reloaded: function() {



            $(document.body).trigger('nmgrcf_crowdfunds_reloaded');



        },







        toggle_options_container: function() {



            var $options_container = $('.' + $(this).attr('data-options'));



            if ($(this).is(':checked')) {



                $options_container.slideDown(200);



            } else {



                $options_container.slideUp(200).find('input').val('');



            }



        },







        /**



         * Disable the ability to toggle the crowdfund input when adding to wishlist



         * if the product already has crowdfund purchases



         */



        update_crowdfund_status_for_wishlist: function() {



            var wishlist_id = $(this).val();



            var in_wishlist = 'data-in-wishlist-' + wishlist_id;



            var is_purchased = 'data-purchased-' + wishlist_id;



            var inputs = $('.nmgr.dialog.show').find('.atw-cf-enable');



            if (inputs.length) {



                $(inputs).each(function() {



                    if ($(this).attr(in_wishlist) === $(this).val() && $(this).attr(is_purchased) === $(this).val()) {



                        this.disabled = true;



                        $(this).next('label').click(function() {



                            if ($(this).attr('data-has-contribution-text')) {



                                alert($(this).attr('data-has-contribution-text'));



                            }



                        });



                    } else {



                        this.disabled = false;



                    }



                });



            }



        }







    };







    nmgr.crowdfunds.init();




    nmgr.free_contributions = {

        container: '#nmgrcf-free-contributions',

        init: function() {
            $(document.body)
                .on('click', '.nmgrcf_fc_add_to_cart_button.nmgr_ajax_add_to_cart', this.add_to_cart);
        },

        reloaded: function() {
            $(document.body).trigger('nmgrcf_free_contributions_reloaded');
        },

        add_to_cart: function(e) {
            var $thisbutton = $(this),
                $form = $thisbutton.closest('form'),
                data = {
                    action: 'nmgrcf_fc_add_to_cart',
                    nmgr_global: JSON.stringify(nmgr_global_params.global)
                };

            e.preventDefault();

            $($form.serializeArray()).each(function(index, obj) {
                data[obj.name] = obj.value;
            });

            $thisbutton.removeClass('added').addClass('loading');

            $.post(nmgr_global_params.ajax_url, data, function(response) {
                if (!response.fragments) {
                    return;
                }

                if (response.fragments.success && response.fragments.redirect_url) {
                    window.location = response.fragments.redirect_url;
                    return;
                }

                $('.woocommerce-error, .woocommerce-message, .woocommerce-info').remove();

                $(document.body).trigger('nmgrcf_free_contribution_add_to_cart_response', response.fragments);
                $thisbutton.removeClass('loading');

                if (response.fragments.notices && 'function' === typeof(Nmeri_showToast)) {
                    response.fragments.notices.forEach(function(item) {
                        Nmeri_showToast(item);
                    });
                }

                if (response.fragments.success) {
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash]); // update cart fragments
                    $(document.body).trigger('nmgrcf_free_contribution_added_to_cart', [response.fragments]);
                }
            });
        }

    };

    nmgr.free_contributions.init();

    nmgrcf_coupons = {
        init: function() {
            $(document.body)
                .on('show.bs.modal', '.nmgrcf-create-coupon-dialog', this.init_create_coupon_form)
                .on('click', '.nmgr-after-table-row.coupons > header .nmgr-action', this.toggle_coupons_table);
        },

        init_create_coupon_form: function() {
            $('select.nmgr-coupon-product-ids').select2();
        },

        toggle_coupons_table: function() {
            $('.nmgrcf-coupons-table').toggleClass('nmgr-hide');
        }
    };

    nmgrcf_coupons.init();

});