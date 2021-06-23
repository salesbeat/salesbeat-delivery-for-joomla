(function () {
    if (!Element.prototype.matches) {
        Element.prototype.matches = Element.prototype.msMatchesSelector ||
            Element.prototype.webkitMatchesSelector;
    }

    if (!Element.prototype.closest) {
        Element.prototype.closest = function(s) {
            let el = this;

            do {
                if (el.matches(s)) return el;
                el = el.parentElement || el.parentNode;
            } while (el !== null && el.nodeType === 1);

            return null;
        };
    }

    SalesbeatJoomShopProduct = {
        init: function(params) {
            this.params = params;

            this.elementBlock = document.querySelector('#' + this.params.main_div_id);
            this.buttonPlus = document.querySelector('.count_block .p_p');
            this.buttonMinus = document.querySelector('.count_block .p_m');
            this.amount = document.querySelector('.count_block .quantity');

            if (this.elementBlock !== null) {
                this.bindEvents();
                this.loadWidget();
            }
        },
        bindEvents: function() {
            let me = this;
            this.buttonPlus.addEventListener('click', function(e) {
                me.changeQuantity(this)
            });

            this.buttonMinus.addEventListener('click', function(e) {
                me.changeQuantity(this)
            });

            this.amount.addEventListener('keyup', function(e) {
                me.changeQuantity(this)
            });
        },
        changeQuantity: function(element)
        {
            let me = this;

            clearTimeout(window.timerChange);
            window.timerChange = setTimeout(function () {
                let amount = element.closest('.count_block'),
                    input = amount.querySelector('input'),
                    value = parseInt(input.value);

                if (value !== 'undefined')
                    me.loadWidget(value);
            }, 700);
        },
        loadWidget: function(quantity) {
            SB.init({
                token: this.params.token,
                price_to_pay: this.params.price_to_pay,
                price_insurance: this.params.price_insurance,
                weight: this.params.weight,
                x: this.params.x,
                y: this.params.y,
                z: this.params.z,
                quantity: quantity || this.params.quantity,
                city_by: this.params.city_by,
                params_by: this.params.params_by,
                main_div_id: this.params.main_div_id,
                callback: function(){
                    console.log('Salesbeat is ready!');
                }
            });
        }
    };

    SalesbeatJoomShopDelivery = {
        isLoadWidget: false,

        init: function(params) {
            this.params = params;

            this.elementBlock = document.querySelector('#sb-cart-widget');
            this.elementResultBlock = document.querySelector('#sb-cart-widget-result');
            this.shippingMethodInput = document.querySelector('#shipping_method_' + this.params.shipping_id);

            this.form = this.elementBlock.closest('form');

            if (this.elementBlock !== null && this.isLoadWidget === false) {
                this.loadWidget();
                this.checkedDelivery();
            }
        },
        loadWidget: function() {
            let me = this,
                origin = window.location.origin;

            this.isLoadWidget = true;

            SB.init_cart({
                token: this.params.token,
                city_code: this.params.city_code,
                products: this.params.products,
                callback: function (data) {
                    me.shippingMethodInput.checked = true;
                    me.sendPost(origin + '/plugins/jshopping/salesbeat/ajax.php', data);
                    me.callbackWidget(data);
                }
            });
        },
        sendPost: function (url, data)
        {
            let xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.onreadystatechange = function() {
                if (xhr.status !== 200)
                    console.log('Ошибка сервера: ' + this.status);

                if (xhr.readyState == 4)
                    oneStepCheckout.updateForm(3);
            };
            xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
            xhr.send(JSON.stringify(data));
        },
        callbackWidget: function(data) {
            let me = this,
                shippingMethodBlock = this.elementBlock.parentNode,
                shippingSumInput = shippingMethodBlock.querySelector('[data-salesbeat-sum]'),
                shippingInfoInput = shippingMethodBlock.querySelector('[data-salesbeat-info]'),
                methodName = data['delivery_method_name'] || 'Не известно';

            let address = '';
            if (data['pvz_address']) {
                address = 'Самовывоз: ' + data['pvz_address']
            } else {
                address = 'Адрес: ';

                address += data['index'] ?
                    data['index'] + ' ' + data['city_name'] :
                    data['city_name'];

                if (data['street']) address += ', ' + data['street'];
                if (data['house']) address += ', дом ' + data['house'];
                if (data['house_block']) address += ' корпус ' + data['house_block'];
                if (data['flat']) address += ', кв. ' + data['flat'];
            }

            let deliveryDays = '';
            if (data['delivery_days']) {
                if (data['delivery_days'] === 0) {
                    deliveryDays = 'сегодня';
                } else if (data['delivery_days'] === 1) {
                    deliveryDays = 'завтра';
                } else {
                    deliveryDays = this.suffixToNumber(data['delivery_days'], ['день', 'дня', 'дней']);
                }
            } else {
                deliveryDays = 'Не известно';
            }

            let deliveryPrice = '';
            if (data['delivery_price']) {
                deliveryPrice = data['delivery_price'] === 0 ?
                    'бесплатно' :
                    this.numberWithCommas(data['delivery_price']) + ' руб';
            } else {
                deliveryPrice = 'Не известно';
            }

            if (data['delivery_price'])
                shippingSumInput.value = data['delivery_price'];
            if (data) {
                let info = '';
                info += 'Способ доставки:' + methodName + '<br>';
                info += 'Стоимость доставки:' + deliveryPrice + '<br>';
                info += 'Срок доставки:' + deliveryDays + '<br>';
                info += 'Адрес:' + address + '<br>';
                if (data['comment'])
                    info += 'Комментари:' + data['comment'] + '<br>';
                shippingInfoInput.value = info;
            }

            let comment = data['comment'] ? '<p> Комментарий: ' + data['comment'] + '</p>' : '';
            this.elementResultBlock.innerHTML += ('<p><span class="salesbeat-summary-label">Способ доставки:</span> ' + methodName + '</p>'
                + '<p><span class="salesbeat-summary-label">Стоимость доставки:</span> ' + deliveryPrice + '</p>'
                + '<p><span class="salesbeat-summary-label">Срок доставки:</span> ' + deliveryDays + '</p>'
                + '<p>' + address + '</p>' + comment
                + '<p><a href="" class="sb-reshow-cart-widget">Изменить данные доставки</a></p>');

            let button = this.elementResultBlock.querySelector('.sb-reshow-cart-widget');
            button.addEventListener('click', function(e) {
                e.preventDefault();
                me.reshowCardWidget();
            });
        },
        reshowCardWidget: function() {
            let shippingMethodBlock = this.elementBlock.parentNode,
                shippingSumInput = shippingMethodBlock.querySelector('[data-salesbeat-sum]'),
                shippingInfoInput = shippingMethodBlock.querySelector('[data-salesbeat-info]');

            SB.reinit_cart(true);
            this.elementResultBlock.innerHTML = '';
            shippingSumInput.value = '';
            shippingInfoInput.value = '';
        },
        suffixToNumber: function(number, suffix) {
            let cases = [2, 0, 1, 1, 1, 2];
            return number + ' ' + suffix[(number % 100 > 4 && number % 100 < 20) ? 2 : cases[(number % 10 < 5) ? number % 10 : 5]];
        },
        numberWithCommas: function(string) {
            return string.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        },
        checkedDelivery: function () {
            let me = this;

            this.form.addEventListener('submit', function(e) {
                let shippingInfoInput = me.form.querySelector('[data-salesbeat-info]');

                if (me.shippingMethodInput.checked && shippingInfoInput.value === '') {
                    e.preventDefault();
                    alert('Пожалуйста, укажите способ доставки');
                    return false;
                } else {
                    return true;
                }
            });
        }
    };
})();