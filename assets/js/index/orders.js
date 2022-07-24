var minsk115Index = {

    _mapApikey: null,
    _baseUrl: "index.php?module=minsk115&action=index",
    src: '',


    map : {
        _map: null,
        _mapId: null,
        _mapCenter: [53.908045, 27.507411],
        _mapZoom: 7,


        /**
         * Инициализация карты
         * @param options
         */
        init: function (options) {

            if (typeof options.mapId != 'string') {
                throw new Error("При инициализации карты не указан или некорректно указан id карты");
            }

            if (typeof options.mapApikey != 'string') {
                throw new Error("При инициализации карты не указан или некорректно указан APIKEY Яндекс карт");
            }

            this._mapId                 = options.mapId;
            minsk115Index._mapApikey = options.mapApikey;

            let promise = minsk115Index.loadYMap(minsk115Index._mapApikey);
            promise.then(function () {
                minsk115Index.map.showMap();

                let promise = minsk115Index.map.loadShops();
                promise.then(function (data) {
                    if ( ! data.shops) {
                        return false;
                    }

                    minsk115Index.map.showShops(data.shops);
                });
            });
        },


        /**
         * Загрузка оборудования
         * @param category
         * @returns {Promise<unknown>}
         */
        loadShops: function (category) {

            return new Promise(function (resolve) {

                $.get(minsk115Index._baseUrl + "&data=get_shops_map",
                    function (data) {
                    },
                    'json'
                )
                    .fail(function (response) {
                        let data = {};

                        try {
                            data = JSON.parse(response.responseText);
                        } catch (e) {
                            console.error(e);
                        }

                        if (data && data.error) {
                            swal("Ошибка", data.error, 'error').catch(swal.noop);

                        } else {
                            swal("Ошибка", 'Не удалось получить координаты цехов', 'error').catch(swal.noop);
                        }
                    })

                    .done(function (data) {
                        if ( ! data || ! data.shops) {
                            let errorMessage = data.error_message || 'Не удалось получить координаты цехов';
                            swal("Ошибка", errorMessage, 'error').catch(swal.noop);
                            return false;
                        }

                        resolve({
                            shops: data.shops
                        });
                    });
            });
        },


        /**
         * Показ карты
         */
        showMap: function () {

            this._map = new ymaps.Map(this._mapId, {
                center: this._mapCenter,
                zoom: this._mapZoom,
                controls: [],
            }, {
                searchControlProvider: 'yandex#map',
                suppressMapOpenBlock: true,
                avoidFractionalZoom: false,
                autoFitToViewport: 'always',
            });
        },


        /**
         * Показ оборудования
         * @param shops
         * @returns {boolean}
         */
        showShops: function (shops) {

            if ( ! shops || shops.length === 0) {
                return false;
            }

            let clusterer = new ymaps.Clusterer();

            $.each(shops, function(index, shop) {
                if (shop.lat && shop.lng) {
                    let title           = shop.hasOwnProperty('title') && shop.title ? shop.title : 'Без названия';
                    let address         = shop.hasOwnProperty('address') && shop.address ? shop.address : '';
                    let departmentTitle = shop.hasOwnProperty('department_title') && shop.department_title ? shop.department_title : '';

                    myPlacemark = new ymaps.Placemark([shop.lat, shop.lng], {
                        hintContent: title,
                        balloonContentHeader: title,
                        balloonContent:
                            'Адрес: ' + address + '<br>' +
                            'Структурное подразделение: ' + departmentTitle
                    }, {});

                    clusterer.add(myPlacemark);
                }
            });

            this._map.geoObjects.add(clusterer);

            setTimeout(function () {
                minsk115Index.map._map.setBounds(
                    minsk115Index.map._map.geoObjects.getBounds(),
                    {checkZoomRange:true}
                );
            }, 250);

            return true;
        }
    },


    addressMap: {
        _map: null,
        _mapContainer: null,
        _mapCenter: [53.908045, 27.507411],
        _mapZoom: 16,
        _mapPlacemark: null,
        _inputCoordinates: null,
        _coordinates: null,
        _inputAddress: null,

        /**
         *
         * @param options
         */
        init: function (options) {

            if (typeof options.mapContainer != 'object') {
                throw new Error("При инициализации карты не указан или некорректно указан параметр mapContainer");
            }

            if (typeof options.inputCoordinates != 'object' && typeof options.coordinates != 'string') {
                throw new Error("При инициализации карты не указан или некорректно указан параметр inputCoordinates");
            }

            if (typeof options.apikey != 'string') {
                throw new Error("При инициализации карты не указан или некорректно указан APIKEY Яндекс карт");
            }

            this._mapContainer          = options.mapContainer;
            this._inputCoordinates      = options.inputCoordinates;
            this._coordinates           = options.coordinates || null;
            this._inputAddress          = options.inputAddress || null;
            this._mapZoom               = options.hasOwnProperty('mapZoom') ? options.mapZoom : this._mapZoom;
            minsk115Index._mapApikey = options.apikey;



            let promise = minsk115Index.loadYMap(minsk115Index._mapApikey);
            promise.then(function () {
                minsk115Index.addressMap.showMap();

                if (minsk115Index.addressMap._inputAddress) {
                    $(minsk115Index.addressMap._inputAddress).keyup(function (event) {

                        if (event.keyCode !== 32 &&
                            event.keyCode !== 37 &&
                            event.keyCode !== 39
                        ) {
                            minsk115Index.addressMap.findAddress($(this).val())
                        }
                    });
                }
            });
        },


        /**
         *
         */
        showMap: function () {

            let lat = '';
            let lng = '';

            if (this._inputCoordinates && $(this._inputCoordinates).val()) {
                let coordinates = $(this._inputCoordinates).val().split(',');
                lat = coordinates[0].trim();
                lng = coordinates[1].trim();

            } else if (this._coordinates) {
                let coordinates = this._coordinates.split(',');
                lat = coordinates[0].trim();
                lng = coordinates[1].trim();
            }

            if ( ! minsk115Index.addressMap._inputAddress && ( ! lat || ! lng)) {
                $(this._mapContainer).hide();
                return false;
            }

            lat = lat || minsk115Index.addressMap._mapCenter[0];
            lng = lng || minsk115Index.addressMap._mapCenter[1];


            minsk115Index.addressMap._map = new ymaps.Map(this._mapContainer, {
                center: [lat, lng],
                zoom: this._mapZoom,
                controls: [],
                dragCursor: 'arrow'
            }, {
                yandexMapDisablePoiInteractivity: true,
                suppressMapOpenBlock: true,
                avoidFractionalZoom: false,
                autoFitToViewport: 'always'
            });



            this._mapPlacemark = new ymaps.Placemark([lat, lng], {}, {
                iconLayout: 'default#image',
                draggable: true
            });

            this._mapPlacemark.events.add("dragend", function (e) {
                let coords = this.geometry.getCoordinates();

                minsk115Index.addressMap.setCoordinates(coords[0], coords[1])
            }, this._mapPlacemark);


            let geoObjects = new ymaps.GeoObjectCollection({}, {
                strokeWidth: 4,
                geodesic: true
            });
            geoObjects.add(this._mapPlacemark);



            minsk115Index.addressMap._map.geoObjects.add(geoObjects);
        },


        /**
         *
         * @param lat
         * @param lng
         * @param setZoom
         */
        setCoordinates: function (lat, lng, setZoom) {

            $(minsk115Index.addressMap._inputCoordinates).val(lat + ', ' + lng);

            minsk115Index.addressMap._mapPlacemark.geometry.setCoordinates([lat, lng]);

            if (setZoom) {
                minsk115Index.addressMap._map.setBounds(minsk115Index.addressMap._map.geoObjects.getBounds(), {
                    checkZoomRange: false,
                });
                minsk115Index.addressMap._map.setZoom(16);
            }
        },


        /**
         * @param address
         */
        findAddress: function (address) {

            let priority = 'Беларусь';
            address = address.trim();

            if (address) {
                $.get("https://geocode-maps.yandex.ru/1.x/?apikey=" + minsk115Index._mapApikey + "&format=json&geocode=" + address,
                    function (data) {

                        let addresses = [];

                        if (data &&
                            data.response &&
                            data.response.GeoObjectCollection &&
                            data.response.GeoObjectCollection.featureMember.length > 0
                        ) {
                            let geoObject = null;

                            $.each(data.response.GeoObjectCollection.featureMember, function (key, object) {

                                if (object &&
                                    object.GeoObject &&
                                    object.GeoObject.metaDataProperty &&
                                    object.GeoObject.metaDataProperty.GeocoderMetaData &&
                                    object.GeoObject.metaDataProperty.GeocoderMetaData.text
                                ) {
                                    addresses.push(object.GeoObject.metaDataProperty.GeocoderMetaData.text);
                                }


                                if ( ! geoObject &&
                                    object &&
                                    object.GeoObject &&
                                    object.GeoObject.description &&
                                    object.GeoObject.description.indexOf(priority) >= 0
                                ) {
                                    geoObject = object.GeoObject;
                                }
                            })


                            if ( ! geoObject &&
                                data.response.GeoObjectCollection.featureMember[0] &&
                                data.response.GeoObjectCollection.featureMember[0].GeoObject &&
                                data.response.GeoObjectCollection.featureMember[0].GeoObject.Point &&
                                data.response.GeoObjectCollection.featureMember[0].GeoObject.Point.pos
                            ) {
                                geoObject = data.response.GeoObjectCollection.featureMember[0].GeoObject;
                            }

                            if (geoObject &&
                                geoObject.Point &&
                                geoObject.Point.pos
                            ) {
                                let coords = geoObject.Point.pos.split(' ');

                                minsk115Index.addressMap.setCoordinates(coords[1], coords[0], true);
                            }
                        }

                        $(minsk115Index.addressMap._inputAddress).autocomplete({
                            source: addresses,
                            minLength: 0
                        });
                    },
                    'json'
                )
            }
        }
    },


    /**
     * @param {Object} form
     */
    rejected: function (form) {

        swal({
            title: 'Укажите причину отклонения',
            input: 'textarea',
            showCancelButton: true,
            confirmButtonColor: '#f0ad4e',
            confirmButtonText: 'Отклонить',
            cancelButtonText: "Отмена",
            showLoaderOnConfirm: true,
            preConfirm: function (comment) {
                return new Promise(function (resolve, reject) {
                    comment = $.trim(comment);
                    if (comment.length === 0) {
                        reject('Не указана причина');
                    } else {
                        $('input[name="moderate_message"]').val(comment);
                        $('input[name="status"]').val('rejected');
                        resolve()
                    }
                })
            }
        }).then(function (comment) {

            xajax_post('SaveOrder', 'index.php?module=minsk115', xajax.getFormValues(form.id));
        }).catch(swal.noop);
    },


    /**
     *
     * @param form
     */
    send115: function (form) {

        swal({
            title: 'Отправить? Уверены?',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#337ab7',
            confirmButtonText: 'Отправить',
            cancelButtonText: "Отмена"
        }).then(function () {
            $('input[name="status"]').val('send115');

            xajax_post('SaveOrder', 'index.php?module=minsk115', xajax.getFormValues(form.id));

        }, function(dismiss) {}).catch(swal.noop);
    },


    /**
     * Загрузка скриптов для яндекс карт
     * @param apikey
     * @returns {Promise<unknown>}
     */
    loadYMap: function (apikey) {

        return new Promise(function (resolve) {
            if (window.hasOwnProperty('ymaps')) {
                resolve();

            } else {
                $.getScript("https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=" + apikey, function (data, textStatus, jqxhr) {
                    ymaps.ready(resolve);
                });
            }
        });
    }
}