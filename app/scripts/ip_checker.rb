class IPChecker

    def initialize
        require 'net/http'

        active_ip = Net::HTTP.get(URI.parse('http://ipecho.net/plain'))
        puts 'Active IP is ' + active_ip

        if (File.exists?('current_ip'))
            recorded_ip = File.read('current_ip')
            puts 'Recorded IP is ' + recorded_ip
        else
            recorded_ip = nil
            puts 'No recorded IP.'
        end

        if (active_ip.eql? recorded_ip)
            puts 'No IP change'
        else
            puts 'IP address has changed'
        end

        self.logMsg('active ip is ' + active_ip)
        fh = File.open('current_ip', 'w')
        fh.puts active_ip
        fh.close
    end

    def logMsg message
        Framework.logMsg 'ip_monitor', message
    end

end

class Framework

    def self.logMsg logfile, message
        fh = File.open(logfile, 'a')
        fh.puts(message)
        fh.close
    end

end
