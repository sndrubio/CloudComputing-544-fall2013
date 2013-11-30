#!/bin/bash

##########################################################################
# This code creates a Elastic Load Balancer on AWS and reports its URL to us
##########################################################################
ELBVAR=`aws elb create-load-balancer --load-balancer-name mp1srs1 --listeners --listeners Protocol=HTTP,LoadBalancerPort=80,InstancePort=80 --availability-zones=us-east-1c --output text`

echo $ELBVAR
#sleep 60 
for i in {0..25}; do echo -ne '.'; sleep 1;done
echo -e "\nFinished launching ELB and sleeping 60 seconds"
aws elb create-lb-cookie-stickiness-policy --load-balancer-name mp1srs1 --policy-name browserSessionStickiness
aws elb set-load-balancer-policies-of-listener --load-balancer-name mp1srs1 --load-balancer-port 80 --policy-names browserSessionStickiness


#########################################################################
#  This code configures the health check and how and what to load balance on
##########################################################################
aws elb configure-health-check --load-balancer-name mp1srs1 --health-check Target=TCP:80,Interval=30,Timeout=5,UnhealthyThreshold=2,HealthyThreshold=10
#sleep 30 
for i in {0..25}; do echo -ne '.'; sleep 1;done
echo -e "\nFinished ELB health check and sleeping 30 seconds"
#########################################################################
# This code declares an array called INSTANCEID and launches two instances and places their instance-ids into an array for further use
########################################################################
declare -a INSTANCEID 
INSTANCEID=(`aws ec2 run-instances --image-id  ami-a9184ac0 --count 2 --instance-type t1.micro --user-data file://install.sh --key-name sandra --security-groups 544-fall2013 --placement AvailabilityZone=us-east-1c --output text | awk {'print $8 '}`)
#sleep 120 
for i in {0..25}; do echo -ne '.'; sleep 1;done
echo -e "\nFinished ec2 run-instances and recording instance-ids into an array"
############################################################################
# This portion takes the instance-ids from 2 steps above and registers the instance-ids with the load balancer
#############################################################################
aws elb register-instances-with-load-balancer --load-balancer-name mp1srs1 --instances  ${INSTANCEID[0]} ${INSTANCEID[1]}

#sleep 30
for i in {0..29}; do echo -ne '.'; sleep 1;done
echo -e "\nDone with registering the instances with the load balancer and sleeping 30 seconds. Give it 5 minutes before the Webbrowser launches..."
############################
# Sleep for five minutes and then launch firefox to the ELB URL
############################
#sleep 120
for i in {0..300}; do echo -ne '.'; sleep 1;done
echo -e "\nNow launching firefox with the load balancer URL"
firefox $ELBVAR & 
