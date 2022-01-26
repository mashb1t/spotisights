docker-compose exec influxdb influx setup \
      --username admin \
      --password passwordpassword \
      --org my-org \
      --bucket influx \
      --force

docker-compose exec influxdb influx auth list \
      --user admin \
      --json

#copy token, add it to script

#http://wiki.webperfect.ch/index.php?title=InfluxDB_2.x:_Error:_Bad_Request_(Grafana_and_InfluxQL)&oldid=2578
#modify data source "influxdb"
#Uncheck "Basic auth" in the Grafana InfluxDB datasource
#Add Custom HTTP Headers in the following format (Important: the Value-field must contain "Token" AND your Token):
#Header: Authorization
#Value: Token <YOUR_TOKEN>
