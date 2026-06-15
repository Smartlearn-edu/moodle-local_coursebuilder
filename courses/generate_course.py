import csv
import xml.etree.ElementTree as ET
from xml.dom import minidom

course_name = "Python Programming: Project-Based Learning"
subject = "Python"

units = [
    {
        "title": "Introduction: Welcome to Python — Your Gateway to Big Tech",
        "unit_name": "Introduction",
        "tag": "[INT]",
        "desc": "Set up a fully working Python development environment, understand the Python ecosystem, write and run your first Python script.",
        "lesson1_title": "Core Concepts: Python Ecosystem",
        "lesson1_content": "<p>Python is the #1 language at Google, Netflix, Amazon, Instagram & Spotify. Key Concepts: What is Python? The ecosystem: pip, virtual environments. Running Python: terminal scripts, Jupyter notebooks.</p>",
        "lesson2_title": "Coding Practice: Setup Verification",
        "lesson2_content": "<p>Complete exercises: Setup Verification, Personal Intro Script, Explore pip.</p>",
        "lesson3_title": "Reference Readings",
        "lesson3_content": "<p>Review the Python Official Tutorial, How Amazon Uses Python (AWS Blog), and Real Python: First Steps.</p>",
        "forum_prompt": "Think of a problem in your daily life that repeats. How could a Python script automate or simplify it?",
        "assignment_title": "Dev Environment Showcase (Amazon Theme)",
        "assignment_desc": "Every Amazon engineer starts Day 1 by setting up their environment exactly right. Prove yours is production-ready.",
        "quiz_title": "Introduction Quiz — Welcome to Python",
        "cat_name": "Introduction - Welcome to Python",
        "questions": [
            {"q": "What command checks your Python version in the terminal?", "c": "python --version (or python3 --version on Mac/Linux)", "w": ["check python", "python -v", "py version"]},
            {"q": "What is pip used for?", "c": "Installing third-party Python packages and libraries", "w": ["Running python scripts", "Formatting code", "Compiling python"]},
            {"q": "True or False: Python is a compiled language.", "c": "False — Python is an interpreted language", "w": ["True — it compiles to machine code", "True — it uses gcc", "False — it is a markup language"]},
            {"q": "Which company created the boto3 library for Python?", "c": "Amazon (for AWS cloud services)", "w": ["Google", "Microsoft", "Meta"]},
            {"q": "What built-in function gets text input from a user?", "c": "input()", "w": ["get()", "read()", "scan()"]}
        ]
    },
    {
        "title": "Unit 1: Python Basics: Variables, Data Types & Operators",
        "unit_name": "Unit 1",
        "tag": "[PY1]",
        "desc": "Declare and use variables, work with int, float, str, bool, apply operators, use f-string formatting.",
        "lesson1_title": "Core Concepts: Variables & Data Types",
        "lesson1_content": "<p>Amazon’s recommendation engine uses core data types: IDs (int), prices (float), names (str), availability (bool). Variables, arithmetic operators, comparison operators, f-strings.</p>",
        "lesson2_title": "Coding Practice: Variables",
        "lesson2_content": "<p>Complete exercises: Product Listing, Price Calculator, String Manipulator.</p>",
        "lesson3_title": "Reference Readings: Built-in Types",
        "lesson3_content": "<p>Review Python Built-in Types, Variables in Python, and f-Strings.</p>",
        "forum_prompt": "If you were designing a Python-based product catalog, what data types would you assign to: rating, number of reviews, prime eligibility, seller name, and delivery date?",
        "assignment_title": "Amazon Product Price Tracker",
        "assignment_desc": "Amazon’s pricing team monitors price changes. Build a mini price-tracker simulating this logic with variables and f-strings.",
        "quiz_title": "Unit 1 Quiz — Python Basics",
        "cat_name": "Unit 1 - Python Basics",
        "questions": [
            {"q": "What data type does price = 9.99 produce?", "c": "float", "w": ["int", "str", "decimal"]},
            {"q": "What is the result of 17 % 5?", "c": "2 (modulo — the remainder after division)", "w": ["3", "3.4", "12"]},
            {"q": "How do you convert the string '42' to an integer?", "c": "int('42')", "w": ["integer('42')", "parse('42')", "to_int('42')"]},
            {"q": "Which f-string format prints price to exactly 2 decimal places?", "c": "f'{price:.2f}'", "w": ["f'{price:2}'", "f'{price.2f}'", "f'{price.round(2)}'"]},
            {"q": "What does in_stock = not False evaluate to?", "c": "True", "w": ["False", "None", "Error"]}
        ]
    },
    {
        "title": "Unit 2: Control Flow: Conditions & Loops",
        "unit_name": "Unit 2",
        "tag": "[PY2]",
        "desc": "Write if/elif/else statements, build for and while loops, use break and continue.",
        "lesson1_title": "Core Concepts: Branching & Repetition",
        "lesson1_content": "<p>Google processes 8.5 billion searches a day using conditions and loops. Learn if / elif / else, nested conditions, for loops, while loops, break, and continue.</p>",
        "lesson2_title": "Coding Practice: Control Flow",
        "lesson2_content": "<p>Complete exercises: Search Filter, Number Guesser, FizzBuzz — Google Edition.</p>",
        "lesson3_title": "Reference Readings: Control Flow",
        "lesson3_content": "<p>Review Python Control Flow, Python for Loops, and List Comprehensions in Python.</p>",
        "forum_prompt": "Google’s spam filter uses conditions. Design a simple rule-based spam classifier in pseudocode with at least 5 conditions.",
        "assignment_title": "Search Query Analyzer (Google Theme)",
        "assignment_desc": "The Google Search Quality team needs a script to analyze a batch of queries and produce a quality report using loops and conditions.",
        "quiz_title": "Unit 2 Quiz — Control Flow",
        "cat_name": "Unit 2 - Control Flow",
        "questions": [
            {"q": "What does range(2, 10, 2) produce?", "c": "[2, 4, 6, 8] — start at 2, stop before 10, step 2", "w": ["[2, 4, 6, 8, 10]", "[1, 3, 5, 7, 9]", "[2, 10]"]},
            {"q": "Which keyword skips to the next loop iteration?", "c": "continue", "w": ["break", "pass", "skip"]},
            {"q": "What happens if a while loop condition is always True with no break?", "c": "Infinite loop — the program never stops", "w": ["The loop skips", "The program pauses", "It runs once"]},
            {"q": "Write a one-line list comprehension: squares of 1 to 5.", "c": "[x**2 for x in range(1, 6)]", "w": ["[x^2 for x in range(5)]", "list(1, 5)^2", "[x*2 in 1..5]"]},
            {"q": "What is the difference between == and = in Python?", "c": "== compares two values (returns bool). = assigns a value to a variable.", "w": ["= compares, == assigns", "They are the same", "== is for strings only"]}
        ]
    },
    {
        "title": "Unit 3: Functions & Modules",
        "unit_name": "Unit 3",
        "tag": "[PY3]",
        "desc": "Define and call functions with parameters and return values. Use default and keyword arguments. Understand variable scope.",
        "lesson1_title": "Core Concepts: Functions",
        "lesson1_content": "<p>Netflix’s recommendation system is built as hundreds of specialized functions. Learn parameters vs arguments, return values, scope (LEGB), docstrings, and modules.</p>",
        "lesson2_title": "Coding Practice: Functions",
        "lesson2_content": "<p>Complete exercises: Pure Functions, Default Args, Module Explorer.</p>",
        "lesson3_title": "Reference Readings: Functions",
        "lesson3_content": "<p>Review Python Functions, Defining Your Own Python Function, and Netflix Tech Blog: Python at Netflix.</p>",
        "forum_prompt": "How would you refactor code you wrote in Unit 1 or 2 into 3 smaller functions following the single-responsibility principle?",
        "assignment_title": "Movie Recommendation Engine (Netflix Theme)",
        "assignment_desc": "Build a simplified version of Netflix’s recommendation logic using Python functions with docstrings and return values.",
        "quiz_title": "Unit 3 Quiz — Functions & Modules",
        "cat_name": "Unit 3 - Functions & Modules",
        "questions": [
            {"q": "What keyword returns a value from a function?", "c": "return", "w": ["output", "yield", "print"]},
            {"q": "What is a default argument? Give an example.", "c": "A parameter with a pre-set value: def greet(name='World'):", "w": ["An argument that cannot be changed.", "An argument that is randomly generated.", "An error handling argument."]},
            {"q": "What does LEGB stand for in Python scope?", "c": "Local, Enclosing, Global, Built-in", "w": ["Logical, External, Global, Boolean", "Local, External, Generic, Basic", "Loop, Execute, Go, Break"]},
            {"q": "Which module would you use to generate a random integer?", "c": "random — use random.randint(a, b)", "w": ["math", "numbers", "calc"]},
            {"q": "What is wrong with: def add(a, b): print(a + b)?", "c": "It prints instead of returning the result. Should be: return a + b", "w": ["It has no type hints.", "It doesn't use f-strings.", "It uses addition."]}
        ]
    },
    {
        "title": "Unit 4: Data Structures: Lists, Dicts, Tuples & Sets",
        "unit_name": "Unit 4",
        "tag": "[PY4]",
        "desc": "Create and manipulate lists, dictionaries, tuples, and sets. Choose the right data structure for a given problem.",
        "lesson1_title": "Core Concepts: Data Structures",
        "lesson1_content": "<p>Spotify manages 100M songs. Playlists are lists, user profiles are dictionaries, artist genres are sets. Learn lists, dicts, tuples, sets, and nesting.</p>",
        "lesson2_title": "Coding Practice: Data Structures",
        "lesson2_content": "<p>Complete exercises: Playlist Builder, User Profile, Top Charts.</p>",
        "lesson3_title": "Reference Readings: Data Structures",
        "lesson3_content": "<p>Review Python Data Structures, Dictionaries in Python, and Spotify Engineering Blog.</p>",
        "forum_prompt": "Design a Python data structure for a Spotify podcast episode. What fields would you include and what type for each?",
        "assignment_title": "Playlist Manager & Music Analytics (Spotify Theme)",
        "assignment_desc": "Build a Spotify-inspired system that stores and analyses music data using lists, dicts, tuples, and sets.",
        "quiz_title": "Unit 4 Quiz — Data Structures",
        "cat_name": "Unit 4 - Data Structures",
        "questions": [
            {"q": "Which data structure guarantees no duplicate values?", "c": "Set {}", "w": ["List []", "Tuple ()", "Dict {}"]},
            {"q": "How do you safely get a dict value that might not exist?", "c": "dict.get('key', default) — avoids KeyError if the key is missing", "w": ["dict['key']", "dict.find('key')", "dict.safe('key')"]},
            {"q": "What is the key difference between a list and a tuple?", "c": "Lists are mutable (changeable); tuples are immutable (fixed after creation)", "w": ["Lists are ordered; tuples are unordered.", "Tuples can hold different types; lists cannot.", "They are the exact same."]},
            {"q": "How do you find elements present in both set_a and set_b?", "c": "set_a & set_b or set_a.intersection(set_b)", "w": ["set_a + set_b", "set_a | set_b", "set_a - set_b"]},
            {"q": "Write a dict comprehension mapping 1–5 to their squares.", "c": "{x: x**2 for x in range(1, 6)}", "w": ["[x: x^2 for x in range(5)]", "map(square, 1..5)", "{x, x*2 for 1 to 5}"]}
        ]
    },
    {
        "title": "Unit 5: File Handling & Exception Management",
        "unit_name": "Unit 5",
        "tag": "[PY5]",
        "desc": "Read and write text and CSV files using Python. Use the with statement and handle errors gracefully.",
        "lesson1_title": "Core Concepts: File I/O & Exceptions",
        "lesson1_content": "<p>Meta’s systems write millions of posts to files. Learn open(), with statement, CSV reading/writing, and try/except/finally structure.</p>",
        "lesson2_title": "Coding Practice: Files & Errors",
        "lesson2_content": "<p>Complete exercises: Post Logger, Safe Calculator, Word Frequency Counter.</p>",
        "lesson3_title": "Reference Readings: Files & Errors",
        "lesson3_content": "<p>Review Python File I/O, Python Exceptions, and Reading and Writing CSV Files.</p>",
        "forum_prompt": "Think of a real app and describe 3 scenarios where file I/O or exception handling is critical.",
        "assignment_title": "Social Feed Logger & Analyser (Meta Theme)",
        "assignment_desc": "Build a simplified backend for a social feed that persists posts to a CSV file and catches exceptions safely.",
        "quiz_title": "Unit 5 Quiz — File Handling & Exception Management",
        "cat_name": "Unit 5 - File Handling & Exception Management",
        "questions": [
            {"q": "Which file mode appends without erasing existing content?", "c": "'a' (append mode)", "w": ["'w'", "'r+'", "'x'"]},
            {"q": "Why use with open(...) instead of just open(...)?", "c": "with automatically closes the file even if an exception occurs", "w": ["It reads the file faster.", "It parses JSON automatically.", "It creates backups."]},
            {"q": "Which exception is raised when a file does not exist?", "c": "FileNotFoundError", "w": ["IOError", "MissingFileError", "NullReferenceException"]},
            {"q": "What does the finally block do?", "c": "Runs always — whether or not an exception occurred — used for cleanup", "w": ["Runs only if an error happens.", "Runs only if no error happens.", "Restarts the program."]},
            {"q": "How do you raise a custom error with a message?", "c": "raise ValueError('your message here')", "w": ["throw Error('message')", "return ValueError('message')", "error('message')"]}
        ]
    },
    {
        "title": "Unit 6: Object-Oriented Programming (OOP)",
        "unit_name": "Unit 6",
        "tag": "[PY6]",
        "desc": "Define classes with attributes and methods. Implement inheritance, encapsulation, and polymorphism.",
        "lesson1_title": "Core Concepts: OOP",
        "lesson1_content": "<p>Uber’s platform uses objects: Driver, Rider, Trip. Learn classes, objects, __init__, instance attributes, methods, inheritance, and super().</p>",
        "lesson2_title": "Coding Practice: OOP",
        "lesson2_content": "<p>Complete exercises: Vehicle Hierarchy, Driver Rating System, Trip History.</p>",
        "lesson3_title": "Reference Readings: OOP",
        "lesson3_content": "<p>Review Python Classes, OOP in Python 3, and Uber Engineering Blog.</p>",
        "forum_prompt": "Choose any real-world system and model it with 3 Python classes. List each class’s attributes and 2 key methods.",
        "assignment_title": "Ride Booking System (Uber Theme)",
        "assignment_desc": "Build a simplified Uber dispatch system using classes, inheritance, and object interactions.",
        "quiz_title": "Unit 6 Quiz — Object-Oriented Programming (OOP)",
        "cat_name": "Unit 6 - Object-Oriented Programming (OOP)",
        "questions": [
            {"q": "What is the purpose of __init__ in a Python class?", "c": "It initializes instance attributes when an object is created (the constructor method)", "w": ["It destroys the object.", "It prints the object.", "It loops the object."]},
            {"q": "What does super().__init__() do?", "c": "Calls the parent class’s constructor — ensures parent attributes are initialized", "w": ["Makes the class super fast.", "Calls a sibling class.", "Deletes parent attributes."]},
            {"q": "What is polymorphism? Give a one-sentence example.", "c": "Same method name behaves differently per subclass", "w": ["A class having many attributes.", "Hiding data from the user.", "Writing code that never crashes."]},
            {"q": "What naming convention marks a private attribute?", "c": "Single underscore prefix: self._balance", "w": ["Double underscore suffix: balance__.", "UPPERCASE: BALANCE.", "private keyword."]},
            {"q": "What does self refer to in a method?", "c": "The specific instance (object) the method is being called on", "w": ["The class itself.", "The parent class.", "The global namespace."]}
        ]
    },
    {
        "title": "Unit 7 — CAPSTONE: APIs, Libraries & Capstone Project",
        "unit_name": "Unit 7",
        "tag": "[PY7]",
        "desc": "Make HTTP requests, parse JSON, use pandas and matplotlib, build a complete data pipeline.",
        "lesson1_title": "Core Concepts: APIs & Pandas",
        "lesson1_content": "<p>Data engineers at Google query APIs for live data, process it with pandas, and visualise it with matplotlib. Learn requests, JSON parsing, pandas DataFrames, and matplotlib charts.</p>",
        "lesson2_title": "Coding Practice: Data Pipeline",
        "lesson2_content": "<p>Complete exercises: Public API Explorer, Pandas Data Cleaner, Matplotlib Dashboard.</p>",
        "lesson3_title": "Reference Readings: External Libraries",
        "lesson3_content": "<p>Review Requests Library Quickstart, pandas Getting Started, and Google Cloud Python Client Libraries.</p>",
        "forum_prompt": "Describe your capstone project: What problem does it solve? Which API will you call? How do skills from all 7 units connect?",
        "assignment_title": "CAPSTONE: Data Dashboard",
        "assignment_desc": "Build a complete data pipeline — fetch real data from a public API, process it, analyse it, and visualise it — just like a Google Data Engineer.",
        "quiz_title": "Unit 7 Quiz — CAPSTONE",
        "cat_name": "Unit 7 - CAPSTONE",
        "questions": [
            {"q": "Which Python library is used to make HTTP requests?", "c": "requests — install with: pip install requests", "w": ["http", "fetch", "web"]},
            {"q": "What does response.json() return?", "c": "A Python dict or list parsed from the JSON response body", "w": ["A string of JSON text.", "An XML document.", "A pandas DataFrame."]},
            {"q": "How do you load a CSV file into a pandas DataFrame?", "c": "df = pd.read_csv('filename.csv')", "w": ["df = pandas.load('filename.csv')", "df = pd.open('filename.csv')", "df = read_file('filename.csv')"]},
            {"q": "What does raise_for_status() do?", "c": "Raises an HTTPError if the response status code is 4xx or 5xx", "w": ["Prints the status code.", "Retries the request.", "Returns True if successful."]},
            {"q": "What pandas method gives count, mean, min, and max for numeric columns?", "c": "df.describe()", "w": ["df.stats()", "df.summary()", "df.info()"]}
        ]
    }
]

def create_csv(units, filename):
    with open(filename, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["type", "section", "name", "intro", "grade", "visible", "completion", "completionview", "timeopen", "timeclose"])
        
        # Course Section
        writer.writerow(["section", "Course Overview", "Course Overview", "", "", "1", "0", "0", "", ""])
        writer.writerow(["label", "Course Overview", "Python Programming Overview", "<div><h2>Welcome to Python</h2><p>Python Programming: Project-Based Learning</p></div>", "", "1", "0", "0", "", ""])
        
        for unit in units:
            u_title = unit["title"]
            u_section = unit["unit_name"]
            
            writer.writerow(["section", u_section, u_section, "", "", "1", "0", "0", "", ""])
            writer.writerow(["label", u_section, u_title, f"<div><h3>{u_title}</h3></div>", "", "1", "0", "0", "", ""])
            
            # Lesson 1
            l1_html = f"<div><h3>Learning Objectives</h3><p>{unit['desc']}</p><h3>{unit['lesson1_title']}</h3>{unit['lesson1_content']}</div>"
            writer.writerow(["page", u_section, f"Lesson 1 — {unit['lesson1_title']}", l1_html, "", "0", "2", "1", "", ""])
            
            # Lesson 2
            l2_html = f"<div><h3>{unit['lesson2_title']}</h3>{unit['lesson2_content']}</div>"
            writer.writerow(["page", u_section, f"Lesson 2 — {unit['lesson2_title']}", l2_html, "", "0", "2", "1", "", ""])
            
            # Lesson 3
            l3_html = f"<div><h3>{unit['lesson3_title']}</h3>{unit['lesson3_content']}</div>"
            writer.writerow(["page", u_section, f"Lesson 3 — {unit['lesson3_title']}", l3_html, "", "0", "2", "1", "", ""])
            
            # Quiz
            qz_html = f"<div><p>5 auto-marked questions loaded from Moodle Question Bank.</p></div>"
            writer.writerow(["quiz", u_section, unit["quiz_title"], qz_html, "50", "0", "2", "1", "", ""])
            
            # Assignment
            as_html = f"<div><p><strong>Instructions</strong><br>{unit['assignment_desc']}</p></div>"
            writer.writerow(["assign", u_section, unit["assignment_title"], as_html, "100", "0", "1", "0", "", ""])
            
            # Forum
            fo_html = f"<div><h3>Discussion Prompt</h3><blockquote>{unit['forum_prompt']}</blockquote></div>"
            writer.writerow(["forum", u_section, f"{unit['unit_name']} Discussion", fo_html, "", "0", "1", "0", "", ""])

def create_xml(units, filename):
    root = ET.Element("quiz")
    
    for unit in units:
        # Category
        cat_q = ET.SubElement(root, "question", type="category")
        cat_cat = ET.SubElement(cat_q, "category")
        cat_text = ET.SubElement(cat_cat, "text")
        cat_text.text = f"$course$/top/Python Programming / {unit['unit_name']} / {unit['cat_name']}"
        
        for q in unit["questions"]:
            mc_q = ET.SubElement(root, "question", type="multichoice")
            
            name = ET.SubElement(mc_q, "name")
            name_text = ET.SubElement(name, "text")
            trunc_q = q["q"][:50] + "..." if len(q["q"]) > 50 else q["q"]
            name_text.text = f"{unit['tag']} {trunc_q}"
            
            qtext = ET.SubElement(mc_q, "questiontext", format="html")
            qtext_text = ET.SubElement(qtext, "text")
            qtext_text.text = f"<![CDATA[<p>{q['q']}</p>]]>"
            
            ET.SubElement(ET.SubElement(mc_q, "generalfeedback", format="html"), "text").text = ""
            ET.SubElement(mc_q, "defaultgrade").text = "1"
            ET.SubElement(mc_q, "penalty").text = "0.3333333"
            ET.SubElement(mc_q, "hidden").text = "0"
            ET.SubElement(mc_q, "single").text = "true"
            ET.SubElement(mc_q, "shuffleanswers").text = "true"
            ET.SubElement(mc_q, "answernumbering").text = "abc"
            
            ET.SubElement(ET.SubElement(mc_q, "correctfeedback", format="html"), "text").text = "Correct!"
            ET.SubElement(ET.SubElement(mc_q, "partiallycorrectfeedback", format="html"), "text").text = ""
            ET.SubElement(ET.SubElement(mc_q, "incorrectfeedback", format="html"), "text").text = "Review the lesson content and try again."
            
            # Answers
            answers = [(q["c"], "100", "Correct!")] + [(w, "0", "") for w in q["w"]]
            for txt, frac, fbk in answers:
                ans = ET.SubElement(mc_q, "answer", fraction=frac, format="html")
                ET.SubElement(ans, "text").text = f"<![CDATA[<p>{txt}</p>]]>"
                ET.SubElement(ET.SubElement(ans, "feedback", format="html"), "text").text = fbk

    # Pretty print XML
    xml_str = ET.tostring(root, encoding="utf-8")
    parsed = minidom.parseString(xml_str)
    # Restore CDATA manually as minidom escapes it
    pretty_xml = parsed.toprettyxml(indent="  ")
    pretty_xml = pretty_xml.replace("&lt;![CDATA[", "<![CDATA[").replace("]]&gt;", "]]>")
    
    with open(filename, "w", encoding="utf-8") as f:
        f.write(pretty_xml)

create_csv(units, "/home/mohammad/Dev/plugins/local/coursebuilder/courses/Python_Course.csv")
create_xml(units, "/home/mohammad/Dev/plugins/local/coursebuilder/courses/Python_CourseQuestions.xml")
print("Successfully generated Python_Course.csv and Python_CourseQuestions.xml")
