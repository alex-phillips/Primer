require 'logger'

class Primer

  @@log_directory = '../logs/'

  def self.log_message (file, message, level='info')
    if !defined? @@logger
      @@logger = Logger.new(@@log_directory + file)
      @@logger.level = Logger::DEBUG
    end
    @@logger.send(level, message)
  end

end