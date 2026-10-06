(function (React, hooks, moduleLibrary, moduleApi, fieldLibrary, rest, i18n) {
  'use strict';

  var e = React.createElement;
  var addAction = hooks.addAction;
  var addFilter = hooks.addFilter;
  var registerModule = moduleLibrary.registerModule;

  var ModuleContainer   = moduleApi.ModuleContainer;
  var StyleContainer    = moduleApi.StyleContainer;
  var elementClassnames = moduleApi.elementClassnames;
  var GroupContainer    = moduleApi.GroupContainer;
  var FieldContainer    = moduleApi.FieldContainer;

  var TextContainer   = fieldLibrary.TextContainer;
  var SelectContainer = fieldLibrary.SelectContainer;
  var useFetch        = rest.useFetch;

  var __ = i18n.__;
  var config = window.happyvrDivi || {};

  var useEffect = React.useEffect;

  function attr(attrs, name) {
    return (attrs && attrs[name] && attrs[name].innerContent
      && attrs[name].innerContent.desktop
      && attrs[name].innerContent.desktop.value) || '';
  }

  function ModuleStyles(props) {
    return e(StyleContainer,
      { mode: props.mode, state: props.state, noStyleTag: props.noStyleTag, orderClass: props.orderClass },
      props.elements.style({ attrName: 'module' })
    );
  }

  function ModuleScriptData(props) {
    return e(React.Fragment, null, props.elements.scriptData({ attrName: 'module' }));
  }

  function moduleClassnames(args) {
    args.classnamesInstance.add(
      elementClassnames({ attrs: (args.attrs && args.attrs.module && args.attrs.module.decoration) || {} })
    );
  }

  function VirtualTourEdit(props) {
    var attrs   = props.attrs;
    var tourId  = attr(attrs, 'tourId');
    var width   = attr(attrs, 'width') || '100%';
    var height  = attr(attrs, 'height') || '400px';
    var sceneId = attr(attrs, 'sceneId');

    var hasTour = !!tourId && tourId !== '0';

    return e(ModuleContainer, {
      attrs: attrs,
      elements: props.elements,
      id: props.id,
      name: props.name,
      moduleClassName: 'happyvr_virtualtour_embed',
      stylesComponent: ModuleStyles,
      scriptDataComponent: ModuleScriptData,
      classnamesFunction: moduleClassnames,
    },
      props.elements.styleComponents({ attrName: 'module' }),
      e('div', { className: 'et_pb_module_inner happyvr_virtualtour_embed__wrap', style: { position: 'relative' } },
        hasTour
          ? [
              e('iframe', {
                key: tourId + ':' + sceneId,
                src: config.previewUrl + '&id=' + encodeURIComponent(tourId)
                  + (sceneId ? '#scene=' + encodeURIComponent(sceneId) : ''),
                title: __('HappyVR Virtual Tour', 'happyvr'),
                style: { width: width, height: height, border: 'none', display: 'block', pointerEvents: 'none' },
                loading: 'lazy',
              }),
            ]
          : e('div', {
              className: 'happyvr_virtualtour_embed__placeholder',
              style: { padding: '40px', textAlign: 'center', background: '#f4f4f4' },
            }, __('HappyVR: select a virtual tour in the module settings.', 'happyvr'))
      )
    );
  }
  
  function TourSettingsContent() {
    var fetchState = useFetch({ data: { options: [] } });
    var fetch = fetchState.fetch, response = fetchState.response, isLoading = fetchState.isLoading;

    useEffect(function () {
      fetch({ method: 'GET', restRoute: '/happyvr/public/v1/virtualtours/options' })
        .catch(function (error) { console.error(error); });
    }, []);

    var list = (response && response.data && response.data.options) || [];
    var tourOptions = { '0': { label: __('Select a virtual tour…', 'happyvr') } };
    list.forEach(function (o) { tourOptions[String(o.value)] = { label: o.label }; });

    return e(React.Fragment, null,
      e(GroupContainer, { id: 'mainContent', title: __('Virtual Tour', 'happyvr') },
        e(FieldContainer, {
          attrName: 'tourId.innerContent',
          label: __('Virtual tour', 'happyvr'),
          features: { sticky: false, responsive: false, hover: false, dynamicContent: false },
        },
          e(SelectContainer, { options: tourOptions })
        ),
        e(FieldContainer, { attrName: 'width.innerContent', label: __('Width', 'happyvr'),
          features: { dynamicContent: false } },
          e(TextContainer, { placeholder: '100%' })),
        e(FieldContainer, { attrName: 'height.innerContent', label: __('Height', 'happyvr'),
          features: { dynamicContent: false } },
          e(TextContainer, { placeholder: '400px' })),
        e(FieldContainer, { attrName: 'sceneId.innerContent', label: __('Initial scene ID', 'happyvr'),
          features: { dynamicContent: false } },
          e(TextContainer, { placeholder: __('default', 'happyvr') }))
      )
    );
  }

  addFilter('divi.iconLibrary.icon.map', 'happyvr', function (icons) {
    var merged = Object.assign({}, icons);
    merged['happyvr/virtualtour-embed-icon'] = {
      name: 'happyvr/virtualtour-embed-icon',
      component: function () {
        return e(React.Fragment, null,
          e('g', null,
            e('path', { d: 'M 6.8598863,5.9004832 4.8181813,8.817205 V 10.02903 C 5.7565771,9.8674104 6.8411066,9.7717503 7.999999,9.7717503 c 1.1588925,0 2.243422,0.09566 3.181818,0.2572797 V 8.817205 l -1.07363,-1.3336487 a 0.24770486,0.24770486 0 0 0 -0.3905524,0.00598 L 9.1487765,8.2420895 A 0.22880567,0.22880567 0 0 1 8.7758732,8.2310359 L 7.2249496,5.9046311 A 0.22107853,0.22107853 0 0 0 6.8598863,5.900482 Z' }),
            e('path', { d: 'm 7.999999,2.4548124 c -1.87174,0 -3.565876,0.2324227 -4.8187138,0.6202059 C 2.5548663,3.2689098 2.0377052,3.499684 1.655007,3.7797412 1.648487,3.7845112 1.642814,3.7898432 1.636364,3.7946562 1.2639217,4.0725155 1,4.4239583 1,4.8411757 V 11.52175 c 0,0.417217 0.2639217,0.76866 0.6363635,1.04652 0.00645,0.0048 0.012128,0.01014 0.018644,0.01492 0.3826982,0.280057 0.8998593,0.510831 1.5262782,0.704723 0.1165068,0.03606 0.2402415,0.06985 0.3641689,0.103159 0.203766,0.05477 0.4140522,0.107222 0.6363636,0.15412 V 12.891423 10.152077 9.4983125 6.8646129 6.2120917 C 3.9575025,6.1624487 3.7456667,6.108029 3.5454542,6.0505151 3.4871989,6.0337803 3.4250245,6.0181515 3.3689627,6.0007992 2.7862141,5.8204244 2.3251416,5.6053455 2.031605,5.3905364 1.7380683,5.1757273 1.6363635,4.9878432 1.6363635,4.8411757 1.6363635,4.6945083 1.7380683,4.507867 2.031605,4.2930579 2.3251416,4.0782487 2.7862141,3.8631698 3.3689627,3.6827952 4.5344604,3.3220459 6.1814837,3.0911759 7.999999,3.0911759 c 1.8185152,0 3.465539,0.23087 4.631037,0.5916193 0.582748,0.1803746 1.043821,0.3954535 1.337357,0.6102627 0.293537,0.2148091 0.395242,0.4014504 0.395242,0.5481178 0,0.1466675 -0.101705,0.3345516 -0.395242,0.5493607 -0.293536,0.2148091 -0.754609,0.429888 -1.337357,0.6102628 -0.05606,0.017352 -0.118237,0.032981 -0.176492,0.049716 -0.200213,0.057514 -0.412048,0.1119336 -0.636364,0.1615766 v 0.6525212 2.6336996 0.6537644 2.739346 0.653765 c 0.222312,-0.0469 0.432598,-0.09935 0.636364,-0.15412 0.123927,-0.03331 0.247662,-0.0671 0.364169,-0.10316 0.626419,-0.193893 1.14358,-0.424667 1.526278,-0.704723 0.0065,-0.0048 0.01219,-0.0101 0.01864,-0.01492 C 14.736078,12.290409 15,11.938966 15,11.521749 V 4.8411757 C 14.999998,4.4239583 14.736076,4.0725155 14.363635,3.794656 14.357135,3.789846 14.351505,3.784509 14.344995,3.779741 13.962295,3.4996836 13.445132,3.2689098 12.818713,3.0750183 11.565875,2.6872351 9.871739,2.4548124 7.999999,2.4548124 Z M 3.8549357,10.230379 c -0.053012,0.0135 -0.1050857,0.02705 -0.1566051,0.04101 0.051519,-0.01396 0.1035927,-0.02752 0.1566051,-0.04101 z m 8.2901263,0 c 0.05301,0.0135 0.105086,0.02705 0.156606,0.04101 -0.05152,-0.01396 -0.103593,-0.02752 -0.156606,-0.04101 z m -8.4467314,2.541726 c 0.051519,0.01396 0.1035927,0.02752 0.1566051,0.04101 -0.053012,-0.01349 -0.1050857,-0.02705 -0.1566051,-0.04101 z m 8.6033374,0 c -0.05152,0.01396 -0.103593,0.02752 -0.156606,0.04101 0.05301,-0.01349 0.105086,-0.02705 0.156606,-0.04101 z'}),
            e('path', { d: 'M 10.545453,5.3172055 A 0.95454533,0.95454533 0 0 1 9.5909079,6.2717508 0.95454533,0.95454533 0 0 1 8.6363626,5.3172055 0.95454533,0.95454533 0 0 1 9.5909079,4.3626601 0.95454533,0.95454533 0 0 1 10.545453,5.3172055 Z'}),
          ),
        );
      }
    };
    return merged;
  });

  addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'happyvr.virtualTour', function () {
    if (!config || !config.metadata) {
      console.error('HappyVR: window.happyvrDivi.metadata is missing, module not registered.');
      return;
    }

    registerModule(config.metadata, {
      renderers: { edit: VirtualTourEdit },
      settings: { content: TourSettingsContent },
      placeholderContent: {
        tourId: { innerContent: { desktop: { value: '0' } } },
        height: { innerContent: { desktop: { value: '400px' } } },
      },
    });
  });
})(
  window.vendor.React,
  window.vendor.wp.hooks,
  window.divi.moduleLibrary,
  window.divi.module,
  window.divi.fieldLibrary,
  window.divi.rest,
  window.vendor.wp.i18n
);