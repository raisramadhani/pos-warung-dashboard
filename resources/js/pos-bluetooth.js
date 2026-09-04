/**
 * POS Bluetooth Printer Module (Clean ESC/POS implementation for Web Bluetooth API).
 *
 * Exposes window.PosBluetooth as a global class.
 */

(function () {
    'use strict';

    const PRINTER_SERVICE_UUIDS = [
        '000018f0-0000-1000-8000-00805f9b34fb', // Standard thermal printer
        '0000ffe0-0000-1000-8000-00805f9b34fb', // HC-05/HC-06 serial
        '0000ff00-0000-1000-8000-00805f9b34fb', // Common printer
        '49535343-fe7d-4ae5-8fa9-9fafd205e455', // HM-10 / MLT-BT05
        '6e400001-b5a3-f393-e0a9-e50e24dcca9e', // Nordic UART (NUS)
    ];

    const ESC = 0x1b;
    const GS = 0x1d;
    const NEWLINE = 0x0a;
    const LINE_WIDTH = 32; // 58mm thermal printer standard (32 columns)

    class PosBluetooth {
        constructor() {
            this.device = null;
            this.server = null;
            this.writeCharacteristic = null;
            this.connected = false;
            this.connecting = false;
            this.printing = false;
            this.onStatusChange = null;
            this._onGattDisconnectedBound = () => this._onDisconnected();
        }

        isSupported() {
            return !!navigator.bluetooth;
        }

        _setStatus(status, message) {
            this.connected = status === 'connected';
            this.connecting = status === 'connecting';
            if (this.onStatusChange) {
                this.onStatusChange(status, message);
            }
        }

        async scan() {
            if (!this.isSupported()) {
                throw new Error('Bluetooth tidak didukung di browser ini.');
            }

            try {
                const device = await navigator.bluetooth.requestDevice({
                    filters: [
                        ...PRINTER_SERVICE_UUIDS.map(uuid => ({ services: [uuid] })),
                        { namePrefix: 'POS' },
                        { namePrefix: 'Printer' },
                        { namePrefix: 'RPP' },
                        { namePrefix: 'MTP' },
                        { namePrefix: 'BT' },
                    ],
                    optionalServices: PRINTER_SERVICE_UUIDS,
                });

                if (this.device) {
                    this.device.removeEventListener('gattserverdisconnected', this._onGattDisconnectedBound);
                }

                this.device = device;
                this.device.addEventListener('gattserverdisconnected', this._onGattDisconnectedBound);

                return this.device;
            } catch (error) {
                if (error.name === 'NotFoundError') {
                    throw new Error('Tidak ada printer yang dipilih.');
                }
                throw error;
            }
        }

        async connect(retryCount = 0) {
            if (!this.device) {
                throw new Error('Tidak ada perangkat yang dipilih.');
            }

            // Bersihkan koneksi lama jika masih tersisa
            if (this.device.gatt?.connected) {
                try {
                    this.device.gatt.disconnect();
                    await new Promise(resolve => setTimeout(resolve, 200));
                } catch {
                    // Ignore disconnect error
                }
            }

            this._setStatus('connecting');

            try {
                const doConnect = async () => {
                    this.server = await this.device.gatt.connect();
                    // Jeda stabilisasi handshake GATT server sebelum discovery service
                    await new Promise(resolve => setTimeout(resolve, 250));

                    if (!this.server || !this.server.connected) {
                        throw new Error('GATT Server is disconnected.');
                    }

                    this.writeCharacteristic = await this._findWriteCharacteristic();
                    return true;
                };

                const timeoutPromise = new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('Koneksi timeout. Pastikan printer menyala dan dalam jangkauan.')), 8000)
                );
                await Promise.race([doConnect(), timeoutPromise]);

                this._setStatus('connected', this.device.name || 'Printer');
                return true;
            } catch (error) {
                // Auto-retry 1x jika disconnect terjadi karena race condition disconnect sebelumnya
                if (retryCount === 0 && error.message && error.message.includes('disconnected')) {
                    if (this.device?.gatt?.connected) {
                        try { this.device.gatt.disconnect(); } catch {}
                    }
                    await new Promise(resolve => setTimeout(resolve, 400));
                    return this.connect(1);
                }

                if (this.device?.gatt?.connected) {
                    this.device.gatt.disconnect();
                }
                this._setStatus('error', 'Gagal terhubung: ' + error.message);
                throw error;
            }
        }

        async _findWriteCharacteristic() {
            if (!this.server || !this.server.connected) {
                throw new Error('GATT server tidak terhubung.');
            }

            const services = await this.server.getPrimaryServices();

            for (const service of services) {
                try {
                    const characteristics = await service.getCharacteristics();
                    for (const char of characteristics) {
                        if (char.properties.write || char.properties.writeWithoutResponse) {
                            return char;
                        }
                    }
                } catch {
                    continue;
                }
            }

            throw new Error('Tidak menemukan characteristic yang bisa ditulis.');
        }

        async scanAndConnect() {
            await this.scan();
            await this.connect();
            return true;
        }

        async disconnect() {
            if (this.device) {
                if (this.device.gatt?.connected) {
                    try {
                        const disconnectPromise = new Promise(resolve => {
                            const handler = () => {
                                this.device?.removeEventListener('gattserverdisconnected', handler);
                                resolve();
                            };
                            this.device?.addEventListener('gattserverdisconnected', handler);
                            setTimeout(resolve, 600); // timeout fallback
                        });
                        this.device.gatt.disconnect();
                        await disconnectPromise;
                    } catch {
                        // ignore error
                    }
                }
            }
            this._onDisconnected();
        }

        _onDisconnected() {
            this.server = null;
            this.writeCharacteristic = null;
            this.connected = false;
            this.connecting = false;
            this.printing = false;
            this._setStatus('disconnected');
        }

        isConnected() {
            return this.connected && this.device?.gatt?.connected;
        }

        async _writeBytes(data) {
            if (!this.writeCharacteristic) {
                throw new Error('Printer tidak terhubung.');
            }

            const CHUNK_SIZE = 16;
            const WRITE_DELAY_MS = 50; // Konsisten 50ms untuk stabilitas mikrokontroler printer lawas
            const bytes = data instanceof Uint8Array ? data : new Uint8Array(data);

            for (let i = 0; i < bytes.length; i += CHUNK_SIZE) {
                const chunk = bytes.slice(i, i + CHUNK_SIZE);
                if (this.writeCharacteristic.properties.writeWithoutResponse) {
                    await this.writeCharacteristic.writeValueWithoutResponse(chunk);
                } else {
                    await this.writeCharacteristic.writeValueWithResponse(chunk);
                }
                if (i + CHUNK_SIZE < bytes.length) {
                    await new Promise(resolve => setTimeout(resolve, WRITE_DELAY_MS));
                }
            }
        }

        async printReceipt(data) {
            if (!this.isConnected()) {
                throw new Error('Printer tidak terhubung. Silakan hubungkan terlebih dahulu.');
            }

            if (this.printing) {
                throw new Error('Printer sedang mencetak, mohon tunggu sebentar.');
            }

            this.printing = true;

            try {
                const encoder = new TextEncoder();
                const commands = [];

                // Initialize printer
                commands.push(ESC, 0x40);

                // Header line breaks
                commands.push(...encoder.encode('\n\n'));

                // Center align: Store name & address
                commands.push(ESC, 0x61, 0x01);
                commands.push(ESC, 0x45, 0x01); // Bold on
                commands.push(ESC, 0x21, 0x00);
                commands.push(...encoder.encode(data.storeName || 'TOKO'));
                commands.push(NEWLINE);
                commands.push(ESC, 0x45, 0x00); // Bold off

                if (data.storeAddress) {
                    commands.push(...encoder.encode(data.storeAddress));
                    commands.push(NEWLINE);
                }

                // Separator
                commands.push(...encoder.encode('================================\n'));

                // Left align: Transaction metadata
                commands.push(ESC, 0x61, 0x00);
                if (data.transactionNumber) {
                    commands.push(...encoder.encode('No   : ' + data.transactionNumber + '\n'));
                }
                if (data.date) {
                    commands.push(...encoder.encode('Tgl  : ' + data.date + '\n'));
                }
                if (data.cashier) {
                    commands.push(...encoder.encode('Kasir: ' + data.cashier + '\n'));
                }
                if (data.customer && data.customer !== 'Umum') {
                    commands.push(...encoder.encode('Pel  : ' + data.customer + '\n'));
                }
                if (data.notes) {
                    commands.push(...encoder.encode('Cat  : ' + data.notes + '\n'));
                }

                commands.push(...encoder.encode('================================\n'));

                // Items listing
                if (data.items && data.items.length > 0) {
                    for (const item of data.items) {
                        const name = item.name || 'Item';
                        commands.push(...encoder.encode(name + '\n'));

                        if (item.originalPrice === 0 || item.price === 0 || name.includes('(GRATIS)')) {
                            const detail = `  ${item.qty} x 0`;
                            const subtotalStr = '0';
                            const padding = LINE_WIDTH - detail.length - subtotalStr.length;
                            commands.push(...encoder.encode(detail + ' '.repeat(Math.max(padding, 1)) + subtotalStr + '\n'));

                            if (item.promotionName) {
                                let promoName = item.promotionName;
                                if (promoName.length > 18) {
                                    promoName = promoName.substring(0, 18);
                                }
                                commands.push(...encoder.encode(`  Promo: ${promoName}\n`));
                            }
                        } else {
                            const originalPrice = item.originalPrice ?? item.price;
                            const originalSubtotal = originalPrice * item.qty;

                            const detail = `  ${item.qty} x ${this._formatRupiah(originalPrice)}`;
                            const subtotalStr = this._formatRupiah(originalSubtotal);
                            const padding = LINE_WIDTH - detail.length - subtotalStr.length;
                            commands.push(...encoder.encode(detail + ' '.repeat(Math.max(padding, 1)) + subtotalStr + '\n'));

                            if (item.discountAmount && item.discountAmount > 0) {
                                let promoName = item.promotionName || 'Diskon';
                                if (promoName.length > 14) {
                                    promoName = promoName.substring(0, 14);
                                }
                                const discLabel = `  Disc (${promoName})`;
                                const discVal = `-${this._formatRupiah(item.discountAmount)}`;
                                const pad = LINE_WIDTH - discLabel.length - discVal.length;
                                commands.push(...encoder.encode(discLabel + ' '.repeat(Math.max(pad, 1)) + discVal + '\n'));
                            }
                        }
                    }
                }

                commands.push(...encoder.encode('--------------------------------\n'));

                // Totals
                commands.push(ESC, 0x61, 0x00);
                if (data.fee && data.fee > 0) {
                    commands.push(...encoder.encode(this._makeLine('Biaya Lain', '+' + this._formatRupiah(data.fee))));
                }

                commands.push(...encoder.encode(this._makeLine('TOTAL', this._formatRupiah(data.total))));
                commands.push(...encoder.encode('================================\n'));

                // Payment summary
                const payLabel = data.paymentMethod === 'qris' ? 'QRIS' : 'Tunai';
                commands.push(...encoder.encode(this._makeLine('Bayar (' + payLabel + ')', this._formatRupiah(data.amountReceived || data.total))));

                if (data.change !== undefined) {
                    commands.push(...encoder.encode(this._makeLine('Kembalian', this._formatRupiah(data.change))));
                }

                commands.push(...encoder.encode('================================\n'));

                // Footer
                commands.push(ESC, 0x61, 0x01);
                commands.push(...encoder.encode('\n'));
                commands.push(...encoder.encode('Terima kasih atas kunjungan\n'));
                commands.push(...encoder.encode('Anda! Sampai jumpa.\n'));
                commands.push(...encoder.encode('\n\n\n\n\n'));

                // Partial paper cut
                commands.push(GS, 0x56, 0x01);

                await this._writeBytes(new Uint8Array(commands));
                // Jeda pendingin buffer sebelum siap terima job cetak baru
                await new Promise(resolve => setTimeout(resolve, 200));
            } finally {
                this.printing = false;
            }
        }

        _makeLine(label, value) {
            const padding = LINE_WIDTH - label.length - value.length;
            return label + ' '.repeat(Math.max(padding, 1)) + value + '\n';
        }

        _formatRupiah(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }
    }

    window.PosBluetooth = PosBluetooth;
})();
