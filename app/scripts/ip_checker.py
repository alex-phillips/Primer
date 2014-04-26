# Python 2.x
import urllib2
active_ip = urllib2.urlopen("http://ipecho.net/plain").read()

# Python 3.x
# import urllib.request
# urllib.request.urlopen("http://ipecho.com/plain").read()

print active_ip

try:
    fh = open('current_ip', 'r')
    recorded_ip = fh.readline()
    fh.close()
    print 'Recorded IP is ' + recorded_ip
except:
    print 'No recorded IP'
    recorded_ip = None
    pass

if active_ip == recorded_ip:
    print 'No IP change'
else:
    print 'New IP address is ' + active_ip

fh = open('current_ip', 'w')
fh.write(active_ip)
fh.close()
