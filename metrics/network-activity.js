window.load_activity_filter = () => {
    let spinner = '<span class="loading-spinner active"></span>'
    jQuery('#activity-list-wrapper').html(`
        <style>
            #activity-list-wrapper li {
            list-style-type: none;
            }
        </style>
        <div id="activity-list">${spinner}</div>
    `)
    jQuery('#activity-filter-wrapper').html(`
        <div class="grid-x" id="filters_section">
        <div class="cell"><button type="button" id="filter_list_button" class="button small filter_list_button hollow">${window.lodash.escape( network_base_script.trans.filter_list ) /*Filter List*/}</button> <span class="loading-spinner"></span></div>
        <div class="cell" id="filters">${spinner}</div>
        <div class="cell"><hr></div>
        <div class="cell">
            <strong>${window.lodash.escape( network_base_script.trans.activity_11 ) /*Time*/}</strong><br>
            <select name="time_range" id="time_range">
                <option value="7">${window.lodash.escape( network_base_script.trans.last_7_days ) /*Last 7 Days*/}</option>
                <option value="30">${window.lodash.escape( network_base_script.trans.last_30_days ) /*Last 30 Days*/}</option>
                <option value="60">${window.lodash.escape( network_base_script.trans.last_60_days ) /*Last 60 Days*/}</option>
                <option value="12m">${window.lodash.escape( network_base_script.trans.last_12_months ) /*Last 12 Months*/}</option>
                <option value="24m">${window.lodash.escape( network_base_script.trans.last_24_months ) /*Last 24 Months*/}</option>
                <option value="this_year">${window.lodash.escape( network_base_script.trans.this_year ) /*This Year*/}</option>
            </select>
        </div>
        <div class="cell">
            <strong>${window.lodash.escape( network_base_script.trans.activity_12 ) /*Result Limit*/}</strong><br>
            <select name="record_limit" id="record_limit">
                <option value="2000">2000</option>
                <option value="5000">5000</option>
                <option value="10000">10000</option>
            </select>
        </div>
        <div class="cell">
            <p><strong>${window.lodash.escape( network_base_script.trans.sites ) /*Sites*/}</strong>  <a style="float:right;" onclick="jQuery('.sites').addClass('hollow');">${window.lodash.escape( network_base_script.trans.activity_13 ) /*uncheck*/}</a> <a style="float:right;" onclick="jQuery('.sites').removeClass('hollow');">${window.lodash.escape( network_base_script.trans.activity_14 ) /*check*/} |&nbsp;</a></p>
            <div id="site-list">${spinner}</div>
        </div>
        <div class="cell">
            <p><strong>${window.lodash.escape( network_base_script.trans.types ) /*Types*/}</strong>  <a style="float:right;" onclick="jQuery('.actions').addClass('hollow');">${window.lodash.escape( network_base_script.trans.activity_13 ) /*uncheck*/}</a> <a style="float:right;" onclick="jQuery('.actions').removeClass('hollow');">${window.lodash.escape( network_base_script.trans.activity_14 ) /*check*/} |&nbsp;</a></p>
            <div id="action-filter">${spinner}</div>
        </div>

    </div>
    <div class="grid-x">
        <div class="cell">
             <hr>
             <button type="button" id="filter_list_button" class="button small filter_list_button hollow">${window.lodash.escape( network_base_script.trans.filter_list ) /*Filter List*/}</button> <span class="loading-spinner"></span>
             <button class="button clear" onclick="reset()">${window.lodash.escape( network_base_script.trans.reset_data ) /*reset data*/}</button> <span class="reset-spinner"></span>
        </div>
    </div>
    `)

    // initialize vars
    let container = jQuery('#activity-list');
    if ( typeof window.activity_filter === 'undefined' ){
        window.activity_filter = {}
    }
    // query and reload data
    function load_data() {
        makeRequest('POST', 'network/base', {'type': 'activity', 'filters': window.activity_filter } )
            .done( data => {
                "use strict";
                window.feed = data
                write_activity_list()
            })
    }
    function load_filters(){
        makeRequest('POST', 'network/base', {'type': 'activity_stats', 'filters': window.activity_filter } )
            .done(function(data) {
                window.activity_stats = data
                window.activity_filter = data.activity_filter
                write_filters()
            })
    }
    load_data()
    load_filters()

    // list to refresh filter button
    jQuery('.filter_list_button').on('click', function(){
      jQuery('#filters').empty().html(spinner)
      jQuery('#activity-list').prepend(spinner)

      jQuery('.loading-spinner').addClass('active')

      let time_range = jQuery('#time_range').val()
      if ( 'this_year' === time_range) {
        var now = new Date();
        var start = new Date(now.getFullYear(), 0, 0);
        var diff = now - start;
        var oneDay = 1000 * 60 * 60 * 24;
        var day = Math.floor(diff / oneDay);
        window.activity_filter.start = '-'+day+' days'
      } else if ( '12m' === time_range) {
        window.activity_filter.start = '-365 days'
      } else if ( '24m' === time_range) {
        window.activity_filter.start = '-24 months'
      } else if ( '60' === time_range) {
        window.activity_filter.start = '-60 days'
      } else if ( '30' === time_range) {
        window.activity_filter.start = '-30 days'
      } else {
        window.activity_filter.start = '-7 days'
      }

      window.activity_filter.limit = jQuery('#record_limit').val()

      // add site filters
      let sites = []
      jQuery.each( jQuery('#filters_section button.sites').not('.hollow'), function(i,v){
        sites.push(v.value)
      })
      window.activity_filter.sites = sites

      // add action filters
      let actions = []
      jQuery.each( jQuery('#filters_section button.actions').not('.hollow'), function(i,v){
        actions.push(v.value)
      })
      window.activity_filter.actions = actions

      load_data()
      load_filters()
      jQuery('.filter_list_button').removeClass('warning').addClass('hollow')
    })


    function write_activity_list(){
        container.empty()

        jQuery.each( window.feed, function(i,v){
          container.append(`<h2>${v.label} (${v.list.length} events)</h2>`)
          container.append(`<ul id="${i}"></ul>`)
          let sublist = jQuery('#'+i)
          jQuery.each(v.list, function(ii,vv){
            sublist.append(`<li><strong>(${vv.time})</strong> ${vv.message} </li>`)
          })
        })

        if ( window.feed.length < 1 ) {
          container.append(`${window.lodash.escape( network_base_script.trans.results ) /*Results*/} : 0`)
        }

      jQuery('.loading-spinner').removeClass('active')
    }
    function write_filters(){

        jQuery('#filters').html(`${window.lodash.escape( network_base_script.trans.results ) /*Results*/}: ${window.activity_stats.records_count}`)

        let site_list = jQuery('#site-list')
        site_list.empty()
        jQuery.each( window.activity_stats.sites, function(sli,slv){
          site_list.append(`<button class="button small sites hollow" id="site-filter-${window.lodash.escape( sli )}" value="${window.lodash.escape( sli )}">${window.lodash.escape( slv )}</button> `)
          if ( jQuery.inArray( sli, window.activity_filter.sites ) >= 0 ) {
            jQuery('#site-filter-'+window.lodash.escape( sli )).removeClass('hollow')
          }
        })

        let action_list = jQuery('#action-filter')
        action_list.empty()
        jQuery.each( window.activity_stats.actions, function(ali,alv){
          action_list.append(`<button class="button small actions hollow" id="action-filter-${window.lodash.escape( ali )}" value="${window.lodash.escape( ali )}">${window.lodash.escape( alv.label )}</button> `)
          if ( jQuery.inArray( ali, window.activity_filter.actions ) >= 0 ) {
            jQuery('#action-filter-'+window.lodash.escape( ali )).removeClass('hollow')
          }
        })

        // list to button changes
        jQuery('#filters_section button').on('click', function(){
          let item = jQuery(this)
          if ( item.hasClass('hollow') ){
            item.removeClass('hollow')
          } else {
            item.addClass('hollow')
          }
          jQuery('.filter_list_button').removeClass('hollow').addClass('warning')
        })
        jQuery('#filters_section select').on('change', function(){
          jQuery('.filter_list_button').removeClass('hollow').addClass('warning')
        })

    }
}
