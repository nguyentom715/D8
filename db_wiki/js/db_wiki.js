(function ($) {
    'use strict';

    /**
     *  UUID Generator
     */
    function uuidv4() {
        return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
            (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
        )
    }

    /**
     * Toggle the Loading div and the article list area between show and hide.
     */
    let toggleLoading = function() {
        $('#loading').toggle();
        $('#db-wiki-articles').toggle();
        // Expand the first section
        $('div.db-articles-section .collapse').first().collapse('show');
    };

    /**
     * Toggle the Loading div and the article list area between show and hide.
     */
    let toggleLoadingMore = function() {
        $('#loading-more').toggle();
        $('#load-more').toggle();
    };

    /**
     * Set various dom elements states depending on the type of view selected.
     * @param type
     */
    let activateView = function(type) {
        let $ds     = $('div#sorting');
        let $lav    = $('li#alpha-view');
        let $lad    = $('li#air-date');
        let $lsd    = $('li#star-date');
        let $ltv    = $('li#timeline-view');
        let $sas    = $('span.alpha-selector');
        let $ssa    = $('span#sort-as');
        let $sva    = $('span#view-as');
        let $ddfa   = $('div#db-filters-alpha');
        let $dfsadc = $('div#db-filters-section-air-date-categories');
        let $dfssdc = $('div#db-filters-section-star-date-categories');

        switch (type) {
            case 'air_date':
                $dfsadc.show();
                $dfssdc.hide();
                $lsd.removeClass('active');
                $lad.addClass('active');
                $ssa.html('Air Date');
                break;

            case 'star_date':
                $dfsadc.hide();
                $dfssdc.show();
                $lad.removeClass('active');
                $lsd.addClass('active');
                $ssa.html('Star Date');
                break;

            case 'timeline':
                $ds.show();
                $ddfa.hide();
                $lav.removeClass('active');
                $ltv.addClass('active');
                if ($lsd.hasClass('active')) {
                    $dfsadc.hide();
                    $dfssdc.show();
                }
                $sva.html('Timeline');
                break;

            case 'alpha':
                $sas.removeClass('active');
                $sas.first().addClass('active');
                $ltv.removeClass('active');
                $lav.addClass('active');
                $ds.hide();
                $ddfa.show();
                $dfsadc.show();
                $dfssdc.hide();
                $sva.html('A-Z List');
                break;
        }
    };


    let filterPath = [];
    let setFilterPath = function(path = []) {
        filterPath = path;
    };

    /**
     * Database Drupal aimed, ajax call.
     * @param actor
     */
    let filterArticlesXHR = function(actor ='') {
        if (actor !== 'load more') {
            toggleLoading();
        } else {
            toggleLoadingMore();
        }
        let filterParams = {};
        $('input:checkbox').each(function(){
            if($(this).is(':checked')) {
                let elementFilter = $(this).attr('data-filter');
                let elementValue = $(this).val();
                if(!(filterParams.hasOwnProperty(elementFilter))) {
                    filterParams[elementFilter] = elementValue;
                } else {
                    filterParams[elementFilter] = filterParams[elementFilter] + '~' + elementValue;
                }
            }
        });

        let modifiedPath = [];
        if(filterParams.hasOwnProperty('movie')) {
            modifiedPath.unshift(filterParams['movie']);
            modifiedPath.unshift('movie');
        }
        if(filterParams.hasOwnProperty('category')) {
            modifiedPath.unshift(filterParams['category']);
            modifiedPath.unshift('category');
        }
        filterPath.forEach(function(element) {
            modifiedPath.push(element);
        });

        // Add `'debug': true` to the payload to enable filter debugging from the controller
        let payloadObject = {
            'path':modifiedPath
        };

        if(actor === 'load more') {
            setNextPageFilter();
            payloadObject.page = nextPage;
        }

        let payload = JSON.stringify(payloadObject);


        $.ajax({
            contentType: 'application/json',
            data: payload,
            dataType: "json",
            url: '/database-filter.json',
            method: 'POST',
            success: function (response) {
                if(actor === 'load more') {
                    let $lmel = $('<div>');
                    $lmel.html(response.html);
                    init($($lmel));

                    // Special handling of sections that load additional article references
                    let $ddasl = $('div.db-articles-section:last');
                    let $ddasf = $('div.db-articles-section:first', $lmel);
                    let lastSectionId = $ddasl.attr('data-section-id');
                    let newSectionId = $ddasf.attr('data-section-id');

                    // If the last section and the first new section have the same data-section-id, append the new section
                    // articles to the old section, and remove the new section.
                    if(lastSectionId === newSectionId) {
                        $('.db-articles-section-content', $ddasl).append($('.db-articles-section-content', $ddasf).html());
                        $ddasf.remove();
                    }
                    $('#db-legacy-sections').append($lmel);
                } else {
                    $('#db-legacy-sections').html(response.html);
                    init();
                }

            },
            complete: function () {
                if (actor === 'alpha') {
                    $('div.db-article').wrapAll("<div class='db-articles-section-content' />");
                }
                if (actor !== 'load more') {
                    toggleLoading();
                } else {
                    toggleLoadingMore();
                }
            }
        });
    };

    let findBootstrapEnvironment = function() {
        let envs = ['xs', 'sm', 'md', 'lg'];
        let $el = $('<div>');
        $el.appendTo($('body'));

        for (let i = envs.length - 1; i >= 0; i--) {
            let env = envs[i];

            $el.addClass('hidden-'+env);
            if ($el.is(':hidden')) {
                $el.remove();
                return env
            }
        }
    };

    let nextPage = 2;
    let setNextPageFilter = function() {
        let dnp = $('.db-next-page-lazy-load:last').attr('data-next-page');
        if (dnp === undefined) {
            nextPage = 2;
        } else {
            nextPage = dnp;
        }
    };

    /**
     * Initialize the formatting on page render as well as on new data load from ajax.
     */
    let init = function(context = window.document) {
        //****************************************
        //** Formatting of Legacy Content       **
        //** This is done here due to the       **
        //** content that is being returned     **
        //** from legacy is already structured. **
        //****************************************

        // Cleanup dom elements that aren't needed
        $('div.db-article-actions', context).remove();
        $('div.db-article-header-separator', context).remove();

        // Adjust the url structure for images, as they are relative src references
        $('a.db-article-image img, img.slideshow__carousel-item-image', context).each(function() {
            $(this).attr('src', 'http://www.tomnguyen.us/' + $(this).attr('src'));
        });

        // Extract the article image from it's anchor parent and remove the anchor.
        $('a.db-article-image', context).each(function(){
            $(this).parent().prepend($(this).html());
            $(this).remove();
        });

        // Adjust the 'Read More' anchor to be a relative url
        $('a.db-article-description-read-more', context).each(function(){
            $(this).attr('href', $(this)[0].pathname);
        });

        // Add some classes for layout.
        $('div.db-article-description', context).addClass('col-xs-12 col-sm-6');
        $('div.db-article-content img', context).addClass('col-xs-12 col-sm-6 db-article-image');
        $('div.db-article-content', context).addClass('row');

        // Move the article image below the article description in the dom tree
        $('.db-article-image', context).each(function(){
            $(this).insertAfter( $(this).next('div') );
        });

        // Wrap the article sections in a parent div to help facilitate functionality and theming
        $('div.db-articles-section', context).wrapAll( "<div id='db-legacy-sections' />");

        // ***********************
        // ** Accordion Section **
        // ***********************

        // Setup the article sections as an outer accordion
        $('div.db-articles-section-title', context).each(function(){
            let uuid = uuidv4();
            let innerText = $(this).html();
            let anchor = $('<a>').html(innerText).attr('href', '#' + uuid).attr('data-toggle', 'collapse').attr('data-role', 'button');
            $(this)
                .html(anchor)
                .next()
                .attr('id', uuid)
                .addClass('collapse');
        });

        // Setup the article teasers as inner accordions
        $('div.db-article-header', context).each(function(){
            let uuid = uuidv4();
            let innerText = $(this).find('span').html();
            let anchor = $('<a>').html(innerText).attr('href', '#' + uuid).attr('data-toggle', 'collapse');
            $(this)
                .html(anchor)
                .next()
                .attr('id', uuid)
                .addClass('collapse');
        });

        //******************
        //** Article Page **
        //******************
        $('div.wysiwyg', context).addClass('col-xs-12 col-md-8');
        $('img.slideshow__carousel-item-image', context).addClass('col-xs-12 col-md-4');

        // Check for bootstrap environment and adjust the navbar content.
        let bsEnv = findBootstrapEnvironment();
        let allowedEnvs = ['xs','sm'];
        if(allowedEnvs.includes(bsEnv)) {
            // Move the filters to be in the navbar dom
            let $dwf = $('#db-wiki-filters');
            let $dwfc = $dwf.clone(true);
            $dwf.remove();

            $('#mobile-nav-subnav').append($dwfc);
        }
    };

    let transposeCategoryFilters = function(alpha = false) {
        let slug, notSlug = '';
        if (alpha) {
            slug = 'air-date';
            notSlug = 'star-date';
        } else {
            if($('ul#sort-selector li.active').data('type') === 'Air Date'){
                slug = 'air-date';
                notSlug = 'star-date';
            } else {
                slug = 'star-date';
                notSlug = 'air-date';
            }
        }
        let filters = [];
        $('div#db-filters-section-' + notSlug + '-categories').find(':checkbox').each(function(){
            if($(this).is(':checked')) {
                filters.push($(this).val());
                $(this).prop("checked", false);
            }
        });
        $('div#db-filters-section-' + slug + '-categories').find(':checkbox').each(function(){
            if(filters.includes($(this).val())) {
                $(this).prop("checked", true);
            }
        });

    };

    $(document).ready(function () {
        init();

        // Don't bother with the loading if the url contains database_article
        if(window.location.href.indexOf("database_article") === -1) {
            toggleLoading();
        }
        $('#load-more').show();

        //*********************
        //** Sorting Section **
        //*********************
        $('#sort-selector li').on('click', function(){
            if ($(this).hasClass('active')) {
                return;
            }

            if($(this).data('type') === 'Air Date'){
                setFilterPath(['sort', 'air_date']);
                filterArticlesXHR();
                activateView('air_date');
            } else {
                setFilterPath();
                filterArticlesXHR();
                activateView('star_date');
            }

            transposeCategoryFilters();

        });

        //*********************
        //** Viewing Section **
        //*********************
        $('#view-selector li').on('click', function(){
            if ($(this).hasClass('active')) {
                return;
            }

            if($(this).data('type') === 'A-Z List'){
                setFilterPath(['view','list']);
                filterArticlesXHR('alpha');
                activateView('alpha');
                transposeCategoryFilters(true);
            } else {
                if ($('li#air-date-sort').hasClass('active')) {
                    setFilterPath(['sort', 'air_date']);
                    filterArticlesXHR();
                } else {
                    setFilterPath();
                    filterArticlesXHR();
                }
                activateView('timeline');
                transposeCategoryFilters();
            }
        });

        //****************************
        //** Alpha Filter Selection **
        //****************************
        $('span.alpha-selector').on('click', function(){
            $('span.alpha-selector').removeClass('active');
            $(this).addClass('active');
            let choice = $(this).attr('data-letter');
            setFilterPath(['letter',  choice, 'view', 'list']);
            filterArticlesXHR('alpha');
        });

        //*********************
        //** Checkbox Filter **
        //*********************
        $('.db-filters-section-toggle-all').on('click', function(){
            $(this).closest('.db-filters-section').find('input').prop("checked", true).change();
            setFilterPath();
            filterArticlesXHR();
        });
        $('.db-filters-section-toggle-none').on('click', function(){
            $(this).closest('.db-filters-section').find('input').change().prop("checked", false);
            setFilterPath();
            filterArticlesXHR();
        });
        $('input:checkbox').on('click', function () {
            filterArticlesXHR();
        });

        //**********************
        //** Load More Button **
        //**********************
        $('a#load-more').on('click', function(e){
            e.preventDefault();
            filterArticlesXHR('load more');
        });

        $('#close-navbar').on('click', function(){
            $('.navbar-collapse').collapse('hide');
        })

    });
})(jQuery);
