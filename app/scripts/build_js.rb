class JsBuilder

  @js_path = '../public/js/'

  def self.build_js
    build_template = <<TEXT
({
      name: 'main',
      baseUrl: './',
      mainConfigFile: './main.js',
      out: './main.min.js',
      optimize: 'uglify2',
      preserveLicenseComments: false,
      wrap: true,
      paths : {
        'js_config' : 'empty:'
      }
})
TEXT

    main_template = File.read @js_path + 'main.js'
    File.rename @js_path + 'main.js', @js_path + 'main.js.template'

    build_js = build_template
    main_js = main_template

    File.open(@js_path + 'build.js', 'w') {|f| f.write(build_js) }
    File.open(@js_path + 'main.js', 'w') {|f| f.write(main_js) }

    compile @js_path + 'build.js', @js_path + 'main.min.js'

    File.rename @js_path + 'main.js.template', @js_path + 'main.js'
    File.delete @js_path + 'build.js'
  end

  def self.compile build_js, built_js
    cwd = File.expand_path @js_path

    cmd = "r.js -o #{build_js}";
    puts cmd
    output = `#{cmd}`
    puts output

    lines = output.split("\n");

    uncompressed_size = 0;
    num_files = 0;
    found_hr = false;

    lines.each do |l|
      if l =~ /----------/
        found_hr = true
        next
      end

      if !found_hr || !l
        next
      end

      num_files+=1
      size = File.size l

      short_file = l.gsub cwd, ''
      short_file = short_file.gsub @js_path, ''

      printf "%-60s %8d\n", short_file, size

      uncompressed_size += size
    end

    compressed_size = File.size built_js

    perc = ((uncompressed_size - compressed_size) / (uncompressed_size + 0.0)) * 100
    perc = perc.round 2

    puts "Uncompressed: #{num_files} files, #{uncompressed_size} bytes"
    puts "Compressed: 1 file, #{compressed_size} bytes (#{perc} % reduction)"
  end

end

JsBuilder.build_js