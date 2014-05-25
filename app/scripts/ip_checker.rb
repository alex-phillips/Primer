require './primer'

class IPChecker

  @monitor_file = './monitors/current_ip'

  def self.run
    require 'net/http'

    active_ip = Net::HTTP.get(URI.parse('http://ipecho.net/plain'))
    self.log_message('Active IP is ' + active_ip)

    if File.exists?(@monitor_file)
        recorded_ip = File.read(@monitor_file)
        self.log_message('Recorded IP is ' + recorded_ip.gsub("\n",''))
    else
        recorded_ip = nil
        self.log_message('No recorded IP.')
    end

    if active_ip.eql? recorded_ip
        self.log_message('No IP change')
    else
        self.log_message('IP address has changed')
    end

    fh = File.open(@monitor_file, 'w')
    fh.puts active_ip
    fh.close
  end

  def self.log_message message
    Primer.log_message 'ip_monitor_ruby', message
    puts message
  end

end

IPChecker.run