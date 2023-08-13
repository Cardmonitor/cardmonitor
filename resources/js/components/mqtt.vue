<template>
    <div>
        <div v-if="!client.connected">
            <div class="form-group">
                <label for="url">URL</label>
                <input type="text" class="form-control" id="url" v-model="connection.url" placeholder="URL">
            </div>
            <div class="form-group">
                <label for="clientId">clientId</label>
                <input type="text" class="form-control" id="clientId" v-model="connection.clientId" placeholder="clientId">
            </div>
            <div class="form-group">
                <label for="username">username</label>
                <input type="text" class="form-control" id="username" v-model="connection.username" placeholder="username">
            </div>
            <div class="form-group">
                <label for="password">password</label>
                <input type="text" class="form-control" id="password" v-model="connection.password" placeholder="password">
            </div>
            <div class="form-group">
                <label for="rejectUnauthorized" class="form-checkbox">rejectUnauthorized</label>
                <input id="rejectUnauthorized" :checked="connection.rejectUnauthorized" type="checkbox" v-model="connection.rejectUnauthorized">
            </div>
            <button class="btn btn-primary btn-sm" @click="createConnection">Connect</button>
            <div v-show="connecting">Connecting...</div>
        </div>
        <div v-else>
            <button class="btn btn-secondary btn-sm" @click="doPublish">Publish</button>
            <button class="btn btn-secondary btn-sm" @click="doSubscribe">Subscribe</button>
            <button class="btn btn-danger btn-sm" @click="destroyConnection">Disconnect</button>
            <div v-show="receiveNews">{{ receiveNews }}</div>
        </div>
    </div>
</template>

<script>
    import mqtt from 'mqtt';

    export default {

        data() {
            return {
                connection: {
                    url: 'wss://broker.emqx.io:8084/mqtt',
                    clientId: 'cardmonitor_' + Math.random().toString(16).substring(2, 8),
                    username: 'emqx_test',
                    password: 'emqx_test',
                    rejectUnauthorized: true,
                    clean: true,
                },
                subscription: {
                    topic: 'cardmonitor',
                    qos: 0,
                },
                publish: {
                    topic: 'cardmonitor',
                    qos: 0,
                    payload: '{"msg": "Hello, I am browser." }',
                },
                receiveNews: "",
                qosList: [0, 1, 2],
                client: {
                    connected: false,
                },
                subscribeSuccess: false,
                connecting: false,
                retryTimes: 0,
            };
        },

        mounted() {
            //
        },

        methods: {
            initData() {
                this.client = {
                    connected: false,
                };
                this.retryTimes = 0;
                this.connecting = false;
                this.subscribeSuccess = false;
            },
            handleOnReConnect() {
                this.retryTimes += 1;
                if (this.retryTimes > 5) {
                    try {
                        this.client.end();
                        this.initData();
                        this.$message.error("Connection maxReconnectTimes limit, stop retry");
                    }
                    catch (error) {
                        this.$message.error(error.toString());
                    }
                }
            },
            createConnection() {
                try {
                    this.connecting = true;
                    const {url, ...options } = this.connection;
                    console.log("createConnection", url, options);
                    this.client = mqtt.connect(url, options);
                    if (this.client.on) {
                        this.client.on("connect", () => {
                            this.connecting = false;
                            this.client.connected = true;
                            this.doSubscribe();
                            console.log("Connection succeeded!");
                        });
                        this.client.on("reconnect", this.handleOnReConnect);
                        this.client.on("error", (error) => {
                            console.log("Connection failed", error);
                        });
                        this.client.on("message", (topic, message) => {
                            this.receiveNews = this.receiveNews.concat(message);
                            console.log(`Received message ${message} from topic ${topic}`);
                        });
                    }
                } catch (error) {
                    this.connecting = false;
                    console.log("mqtt.connect error", error);
                }
            },
            doSubscribe() {
                const { topic, qos } = this.subscription
                this.client.subscribe(topic, { qos }, (error, res) => {
                    if (error) {
                        console.log('Subscribe to topics error', error)
                        return
                    }
                    this.subscribeSuccess = true
                    console.log('Subscribe to topics res', res)
                })
            },
            doPublish() {
                console.log('doPublish');
                const { topic, qos, payload } = this.publish
                this.client.publish(topic, payload, { qos }, error => {
                    if (error) {
                        console.log('Publish error', error)
                    }
                })
            },
            destroyConnection() {
                if (!this.client.connected) {
                    return;
                }

                try {
                    this.client.end(false, () => {
                        this.initData();
                        console.log('Successfully disconnected!')
                    })
                } catch (error) {
                    console.log('Disconnect failed', error.toString())
                }
            }
        }
    };
</script>
